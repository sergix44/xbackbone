import ScreenCloud
import json
import traceback
import urllib.request
import urllib.error
import urllib.parse
import io
import mimetypes
import uuid

from PythonQt.QtCore import QByteArray, QBuffer, QIODevice, QFile
from PythonQt.QtGui import QWidget, QDialog
from PythonQt.QtUiTools import QUiLoader


class XBackBoneUploader:

    def __init__(self):
        self.uil = QUiLoader()
        self.config_path = workingDir + '/config.json'
        self.loadSettings()

    def showSettingsUI(self, parentWidget):
        self.parentWidget = parentWidget
        self.settingsDialog = self.uil.load(QFile(workingDir + '/settings.ui'), parentWidget)

        self.settingsDialog.connect('accepted()', self.saveSettings)
        self.loadSettings()

        self.settingsDialog.group_url.token.text = self.token
        self.settingsDialog.group_url.host.text = self.host

        self.settingsDialog.open()

    def loadSettings(self):
        with open(self.config_path, 'r') as config:
            settings = json.load(config)

            self.token = settings.get('token')
            self.host = settings.get('host')

    def saveSettings(self):
        data = {
            'token': self.settingsDialog.group_url.token.text,
            'host': self.settingsDialog.group_url.host.text
        }
        with open(self.config_path, 'w') as config:
            json.dump(data, config)

    def isConfigured(self):
        self.loadSettings()
        return not (not self.token or not self.host)

    def getFilename(self):
        name = ScreenCloud.formatFilename('screenshot_%Y-%m-%d_%H-%M-%S')
        return name + '.' + ScreenCloud.getScreenshotFormat().lower()

    def upload(self, screenshot, name):
        self.loadSettings()

        q_ba = QByteArray()
        q_buff = QBuffer(q_ba)

        q_buff.open(QIODevice.WriteOnly)
        screenshot.save(q_buff, ScreenCloud.getScreenshotFormat())
        q_buff.close()

        url = self.host.rstrip('/') + '/api/v1/upload'

        form = MultiPartForm()
        form.add_file('file', self.getFilename(), q_ba.data())
        data = bytes(form)

        r = urllib.request.Request(url, data, headers={
            'Authorization': 'Bearer ' + self.token,
            'Accept': 'application/json',
            'Content-Type': form.get_content_type(),
            'Content-Length': len(data),
            'User-Agent': 'XBackBone/Screencloud-client'
        })

        try:
            res = urllib.request.urlopen(r)
            response = json.loads(res.read().decode('utf-8'))
            resource = response.get('data') or {}
            url = resource.get('preview_ext_url')

            if not url:
                raise Exception(response.get('message'))

            ScreenCloud.setUrl(url)

        except urllib.error.HTTPError as e:
            try:
                response = json.loads(e.read().decode('utf-8'))
                message = response.get('message') or str(e)
            except Exception:
                message = str(e)
            ScreenCloud.setError('Error while connecting to: ' + self.host + '\n' + message)
            return False

        except Exception as e:
            try:
                ScreenCloud.setError('Could not upload to: ' + self.host + '\nError: ' + str(e))
            except AttributeError:
                ScreenCloud.setError('Unexpected error while uploading:\n' + traceback.format_exc())
            return False

        return True


class MultiPartForm:
    def __init__(self):
        self.form_fields = []
        self.files = []
        self.boundary = uuid.uuid4().hex.encode('utf-8')
        return

    def get_content_type(self):
        return 'multipart/form-data; boundary={}'.format(self.boundary.decode('utf-8'))

    def add_field(self, name, value):
        self.form_fields.append((name, value))

    def add_file(self, fieldname, filename, body, mimetype=None):
        if mimetype is None:
            mimetype = (mimetypes.guess_type(filename)[0] or 'application/octet-stream')
        self.files.append((fieldname, filename, mimetype, body))

    @staticmethod
    def _form_data(name):
        return ('Content-Disposition: form-data; name="{}"\r\n').format(name).encode('utf-8')

    @staticmethod
    def _attached_file(name, filename):
        return ('Content-Disposition: file; name="{}"; filename="{}"\r\n').format(name, filename).encode('utf-8')

    @staticmethod
    def _content_type(ct):
        return 'Content-Type: {}\r\n'.format(ct).encode('utf-8')

    def __bytes__(self):
        buffer = io.BytesIO()
        boundary = b'--' + self.boundary + b'\r\n'

        # Add the form fields
        for name, value in self.form_fields:
            buffer.write(boundary)
            buffer.write(self._form_data(name))
            buffer.write(b'\r\n')
            buffer.write(value.encode('utf-8'))
            buffer.write(b'\r\n')

        # Add the files to upload
        for f_name, filename, f_content_type, body in self.files:
            buffer.write(boundary)
            buffer.write(self._attached_file(f_name, filename))
            buffer.write(self._content_type(f_content_type))
            buffer.write(b'\r\n')
            buffer.write(body)
            buffer.write(b'\r\n')

        buffer.write(b'--' + self.boundary + b'--\r\n')
        return buffer.getvalue()
