#!/usr/bin/env python3
"""XBackBone KDE Purpose "Export" plugin.

KDE's Purpose framework (Purpose::ProcessJob) launches this script as a subprocess when the
user picks XBackBone from the "Share" menu in Spectacle, Dolphin, and other KDE apps. It:

  1. Connects to the QLocalSocket passed via --server.
  2. Reads the length-prefixed CBOR input (keys: "urls", "mimeType").
  3. Uploads each file to {XBB_URL}/api/v1/upload using the token from the shared
     ~/.config/xbackbone/config file (the same file the `xbb` CLI uploader uses).
  4. Writes newline-delimited JSON back to the socket. Recognised keys: "percent",
     "error", "errorText", "output" (the outbound "url" argument).

Standard library only, so it runs anywhere Python 3 is available with no extra packages.

Debugging: `main.py --selftest <file>` uploads a file directly and prints the URL,
bypassing the Purpose socket protocol.
"""
import io
import json
import logging
import logging.handlers
import mimetypes
import os
import shutil
import socket
import subprocess
import sys
import urllib.error
import urllib.request
import uuid
from urllib.parse import urlparse
from urllib.request import url2pathname

logger = logging.getLogger('xbackbone')


def configure_logging() -> str:
    """Log to stderr (forwarded to the launching app) and to a rotating file under
    $XDG_STATE_HOME/xbackbone so uploads can be traced after the fact. Returns the log path."""
    logger.setLevel(logging.DEBUG)
    formatter = logging.Formatter('%(asctime)s [%(levelname)s] %(message)s')

    stream = logging.StreamHandler()
    stream.setFormatter(formatter)
    logger.addHandler(stream)

    state_home = os.environ.get('XDG_STATE_HOME') or os.path.expanduser('~/.local/state')
    log_path = os.path.join(state_home, 'xbackbone', 'kde-plugin.log')
    try:
        os.makedirs(os.path.dirname(log_path), exist_ok=True)
        rotating = logging.handlers.RotatingFileHandler(log_path, maxBytes=512 * 1024, backupCount=2)
        rotating.setFormatter(formatter)
        logger.addHandler(rotating)
    except OSError:
        pass
    return log_path


def mask_token(token: str) -> str:
    """Redact a Sanctum token for logs, keeping only its numeric id prefix."""
    return token.split('|', 1)[0] + '|***' if '|' in token else '***'


# --- Minimal, stdlib-only CBOR decoder (enough for the Purpose input: maps/arrays/text/ints) ---

class _CborReader:
    def __init__(self, data: bytes):
        self.data = data
        self.pos = 0

    def _read(self, n: int) -> bytes:
        chunk = self.data[self.pos:self.pos + n]
        if len(chunk) != n:
            raise ValueError('unexpected end of CBOR input')
        self.pos += n
        return chunk

    def _read_uint(self, info: int) -> int:
        if info < 24:
            return info
        if info == 24:
            return self._read(1)[0]
        if info == 25:
            return int.from_bytes(self._read(2), 'big')
        if info == 26:
            return int.from_bytes(self._read(4), 'big')
        if info == 27:
            return int.from_bytes(self._read(8), 'big')
        raise ValueError(f'unsupported CBOR length info: {info}')

    def _peek_break(self) -> bool:
        if self.data[self.pos] == 0xFF:
            self.pos += 1
            return True
        return False

    def decode(self):
        initial = self._read(1)[0]
        major = initial >> 5
        info = initial & 0x1F

        if major == 0:
            return self._read_uint(info)
        if major == 1:
            return -1 - self._read_uint(info)
        if major == 2:
            return self._read(self._read_uint(info))
        if major == 3:
            return self._read(self._read_uint(info)).decode('utf-8')
        if major == 4:
            if info == 31:
                out = []
                while not self._peek_break():
                    out.append(self.decode())
                return out
            return [self.decode() for _ in range(self._read_uint(info))]
        if major == 5:
            out = {}
            if info == 31:
                while not self._peek_break():
                    key = self.decode()
                    out[key] = self.decode()
                return out
            for _ in range(self._read_uint(info)):
                key = self.decode()
                out[key] = self.decode()
            return out
        if major == 7:
            if info == 20:
                return False
            if info == 21:
                return True
            if info in (22, 23):
                return None
        raise ValueError(f'unsupported CBOR item: major={major} info={info}')


def cbor_decode(data: bytes):
    return _CborReader(data).decode()


# --- Multipart form encoder (borrowed from the ScreenCloud integration) ---

class MultiPartForm:
    def __init__(self):
        self.files = []
        self.boundary = uuid.uuid4().hex.encode('utf-8')

    def get_content_type(self) -> str:
        return 'multipart/form-data; boundary={}'.format(self.boundary.decode('utf-8'))

    def add_file(self, fieldname, filename, body, mimetype=None):
        if mimetype is None:
            mimetype = mimetypes.guess_type(filename)[0] or 'application/octet-stream'
        self.files.append((fieldname, filename, mimetype, body))

    def __bytes__(self) -> bytes:
        buffer = io.BytesIO()
        boundary = b'--' + self.boundary + b'\r\n'
        for fieldname, filename, content_type, body in self.files:
            buffer.write(boundary)
            buffer.write('Content-Disposition: file; name="{}"; filename="{}"\r\n'.format(fieldname, filename).encode('utf-8'))
            buffer.write('Content-Type: {}\r\n'.format(content_type).encode('utf-8'))
            buffer.write(b'\r\n')
            buffer.write(body)
            buffer.write(b'\r\n')
        buffer.write(b'--' + self.boundary + b'--\r\n')
        return buffer.getvalue()


# --- XBackBone configuration & upload ---

class ConfigError(Exception):
    pass


class UploadError(Exception):
    pass


def read_config() -> tuple[str, str]:
    """Read XBB_URL/XBB_TOKEN from ~/.config/xbackbone/config (shared with the xbb CLI)."""
    config_home = os.environ.get('XDG_CONFIG_HOME') or os.path.expanduser('~/.config')
    path = os.path.join(config_home, 'xbackbone', 'config')
    if not os.path.isfile(path):
        raise ConfigError(
            'XBackBone is not configured. Expected credentials in '
            f'{path} (XBB_URL and XBB_TOKEN).'
        )

    values = {}
    with open(path, encoding='utf-8') as handle:
        for line in handle:
            line = line.strip()
            if not line or line.startswith('#') or '=' not in line:
                continue
            key, _, value = line.partition('=')
            values[key.strip()] = value.strip().strip('"').strip("'")

    url = values.get('XBB_URL')
    token = values.get('XBB_TOKEN')
    if not url or not token:
        raise ConfigError(f'XBB_URL and XBB_TOKEN must both be set in {path}.')
    logger.debug('config loaded from %s (url=%s, token=%s)', path, url.rstrip('/'), mask_token(token))
    return url.rstrip('/'), token


def upload_file(url: str, token: str, path: str, mimetype=None) -> str:
    with open(path, 'rb') as handle:
        body = handle.read()

    logger.info('uploading %s (%d bytes) to %s/api/v1/upload', os.path.basename(path), len(body), url)

    form = MultiPartForm()
    form.add_file('file', os.path.basename(path), body, mimetype)
    payload = bytes(form)

    request = urllib.request.Request(url + '/api/v1/upload', payload, headers={
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json',
        'Content-Type': form.get_content_type(),
        'Content-Length': str(len(payload)),
        'User-Agent': 'XBackBone/KDE-Purpose',
    })

    try:
        with urllib.request.urlopen(request) as response:
            data = json.loads(response.read().decode('utf-8')).get('data') or {}
    except urllib.error.HTTPError as error:
        try:
            message = json.loads(error.read().decode('utf-8')).get('message') or str(error)
        except Exception:
            message = str(error)
        raise UploadError(message) from error
    except urllib.error.URLError as error:
        raise UploadError(f'Could not reach {url}: {error.reason}') from error

    link = data.get('preview_ext_url')
    if not link:
        raise UploadError('The server response did not contain a share link.')
    logger.info('uploaded %s -> %s', os.path.basename(path), link)
    return link


def path_from_url(entry: str) -> str:
    parsed = urlparse(entry)
    if parsed.scheme in ('', 'file'):
        return url2pathname(parsed.path)
    raise UploadError(f'Unsupported URL scheme for {entry!r}; only local files are supported.')


def copy_to_clipboard(text: str) -> None:
    """Best-effort clipboard copy. Fire-and-forget: wl-copy/xclip keep running to own the
    selection, so we must detach and redirect their stdio (otherwise they'd hold our
    output streams open and stall the caller)."""
    for tool in (['wl-copy'], ['xclip', '-selection', 'clipboard']):
        if not shutil.which(tool[0]):
            continue
        try:
            proc = subprocess.Popen(
                tool,
                stdin=subprocess.PIPE,
                stdout=subprocess.DEVNULL,
                stderr=subprocess.DEVNULL,
                start_new_session=True,
            )
            proc.stdin.write(text.encode('utf-8'))
            proc.stdin.close()
        except Exception:
            pass
        return


def notify(summary: str, body: str, icon: str = 'xbackbone') -> None:
    """Best-effort desktop notification via notify-send. Detached and non-blocking; silenced
    when XBB_NO_NOTIFY is set (used by tests) or notify-send is unavailable."""
    if os.environ.get('XBB_NO_NOTIFY') or not shutil.which('notify-send'):
        return
    try:
        subprocess.Popen(
            ['notify-send', '--app-name=XBackBone', '--icon', icon, summary, body],
            stdin=subprocess.DEVNULL,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            start_new_session=True,
        )
    except Exception:
        pass


def notify_success(url: str) -> None:
    """Show a "🚀 Upload completed!" notification; clicking it opens the uploaded URL.

    notify-send --action waits for the user to click, so this runs in a fully detached helper
    that outlives the (short-lived) plugin process — the Purpose job still finishes at once.
    """
    if os.environ.get('XBB_NO_NOTIFY') or not shutil.which('notify-send'):
        return
    summary = '🚀 Upload completed!'
    notify_send = shutil.which('notify-send')
    xdg_open = shutil.which('xdg-open')
    try:
        if xdg_open:
            # $1 notify-send, $2 summary, $3 xdg-open, $4 url (shown as the body and opened on
            # click). The action name is printed on click; open the URL only then (empty output
            # means the notification just expired).
            helper = 'action=$("$1" --app-name=XBackBone --icon=xbackbone --action=default=Open -- "$2" "$4"); ' \
                     '[ -n "$action" ] && exec "$3" "$4"'
            command = ['sh', '-c', helper, 'xbb-notify', notify_send, summary, xdg_open, url]
        else:
            command = ['notify-send', '--app-name=XBackBone', '--icon=xbackbone', summary, url]
        subprocess.Popen(
            command,
            stdin=subprocess.DEVNULL,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            start_new_session=True,
        )
    except Exception:
        pass


def upload_all(urls, mimetype=None) -> list[str]:
    url, token = read_config()
    links = []
    for entry in urls:
        links.append(upload_file(url, token, path_from_url(entry), mimetype))
    if not links:
        raise UploadError('No files to upload.')
    copy_to_clipboard('\n'.join(links))
    return links


# --- Purpose process protocol ---

def arg_value(name: str):
    flag = '--' + name
    for index, item in enumerate(sys.argv):
        if item == flag and index + 1 < len(sys.argv):
            return sys.argv[index + 1]
        if item.startswith(flag + '='):
            return item[len(flag) + 1:]
    return None


def run_purpose_job(server: str) -> int:
    logger.debug('connecting to Purpose socket %s', server)
    sock = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
    sock.connect(server)

    def send(obj):
        sock.sendall((json.dumps(obj) + '\n').encode('utf-8'))

    try:
        reader = sock.makefile('rb')
        payload_len = int(reader.readline().strip())
        data = cbor_decode(reader.read(payload_len))

        urls = data.get('urls') or []
        mimetype = data.get('mimeType') or None
        logger.info('received %d url(s), mimeType=%s', len(urls), mimetype)

        links = upload_all(urls, mimetype)
        send({'percent': 100})
        send({'output': {'url': links[0]}})

        notify_success(links[0])
        logger.info('job finished successfully (%d file(s))', len(links))
    except (ConfigError, UploadError) as error:
        logger.error('upload failed: %s', error)
        send({'error': 1, 'errorText': str(error)})
        notify('❌ Upload failed', str(error), icon='dialog-error')
    except Exception as error:  # noqa: BLE001 - report anything else back to the UI
        logger.exception('unexpected error')
        send({'error': 1, 'errorText': f'Unexpected error: {error}'})
        notify('❌ Upload failed', f'Unexpected error: {error}', icon='dialog-error')
    finally:
        sock.close()
    return 0


def main() -> int:
    log_path = configure_logging()
    logger.info('xbackbone-kde invoked: %s', ' '.join(sys.argv[1:]) or '(no args)')
    logger.debug('logging to %s', log_path)

    selftest = arg_value('selftest')
    if selftest:
        try:
            print(upload_all([selftest])[0])
            return 0
        except (ConfigError, UploadError) as error:
            logger.error('selftest upload failed: %s', error)
            print(f'error: {error}', file=sys.stderr)
            return 1

    server = arg_value('server')
    if not server:
        logger.error('missing --server argument')
        print('missing --server', file=sys.stderr)
        return 2
    return run_purpose_job(server)


if __name__ == '__main__':
    sys.exit(main())
