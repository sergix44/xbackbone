# XBackBone — KDE "Share" (Purpose) plugin

Adds an **Upload to XBackBone** entry to the KDE **Share** menu, so you can send a capture
straight to your XBackBone instance from **Spectacle → Export → Share**, from **Dolphin →
right-click → Share**, and from any other KDE app that offers the Share menu.

It is a no-compile [Purpose](https://api.kde.org/frameworks/purpose/html/index.html)
KPackage plugin: a small Python script driven by KDE's Purpose framework. It requires only
**Python 3** — no extra packages.

## Install

From your instance's **Integrations** page, download the installer (a single shell script,
pre-filled with your instance URL and a personal upload token) and run it:

```sh
bash xbackbone-kde-install.sh
```

Then restart Spectacle (and Dolphin). The uploader appears under **Export → Share**.

The installer is self-contained — it embeds the plugin metadata, the Python uploader and the
icons — and:

- writes your credentials to `~/.config/xbackbone/config` (`XBB_URL` / `XBB_TOKEN`) — the
  same file the [`xbb` CLI uploader](../xbb) uses, so the two integrations share one
  configuration (an existing config is backed up to `config.bak`);
- installs the plugin into `~/.local/share/kpackage/Purpose/xbackbone/`;
- installs the XBackBone icon into the hicolor icon theme.

Uninstall with `bash xbackbone-kde-install.sh --uninstall` (your config is left untouched).

## How it works

KDE's Purpose framework discovers the package under `kpackage/Purpose/` and runs
`contents/code/main.py` as a subprocess when you pick XBackBone from the Share menu. The
script reads the shared config, uploads each file to `POST {XBB_URL}/api/v1/upload` with a
`Bearer` token, and returns the resulting share link. On completion it shows a desktop
notification (via `notify-send`) and copies the link to the clipboard (via `wl-copy` or
`xclip`, when available).

Activity is logged to `$XDG_STATE_HOME/xbackbone/kde-plugin.log` (usually
`~/.local/state/xbackbone/kde-plugin.log`) and to stderr.

## Layout of this directory

These are the *sources* of the plugin. The downloadable installer is assembled from them by
`XBB\Actions\Integration\GenerateKdePlugin`, which fills `installer.sh.stub` with the
instance name, a fresh token, the uploader, and the base64-encoded icons.

- `metadata.json` — Purpose plugin metadata (`@@APP_NAME@@` is replaced at generation time).
- `contents/code/main.py` — the uploader (also runnable directly for debugging, see below).
- `icons/xbackbone-{32,192,512}.png` — the icon, installed into the hicolor theme.
- `installer.sh.stub` — the self-contained installer template.

## Troubleshooting

- **The entry doesn't show up:** restart the KDE app so Purpose rescans, and make sure
  `~/.local/share/kpackage/Purpose/xbackbone/contents/code/main.py` is executable.
- **"XBackBone is not configured":** re-run the installer, or create
  `~/.config/xbackbone/config` with `XBB_URL` and `XBB_TOKEN` lines.
- **Debug an upload directly:** `contents/code/main.py --selftest /path/to/file` uploads a
  file and prints the URL, bypassing the Purpose socket protocol.
