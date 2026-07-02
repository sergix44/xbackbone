# Spectacle & KDE Share menu

<Badge type="tip" text="Linux" /> <Badge type="tip" text="KDE" />

XBackBone integrates with KDE's [Purpose](https://api.kde.org/frameworks/purpose/html/index.html)
framework, which powers the **Share** menu across KDE. Once installed, an **Upload to
XBackBone** entry appears in:

- **[Spectacle](https://apps.kde.org/spectacle/)** — after a capture, under **Export → Share**;
- **Dolphin** — right-click a file → **Share**;
- any other KDE app that offers the Share menu.

Picking it uploads the file to your instance and copies the share link to your clipboard.

The plugin is a small Python script (no compilation, no extra packages — just Python 3) and
shares its configuration with the [CLI uploader](/clients/cli-scripts).

## Install

1. Open the **Integrations** page in your XBackBone instance and click **Download package**
   on the *KDE* card. You get a single, self-contained installer script, pre-filled with
   your instance URL and a personal upload token.
2. Run it:

   ```sh
   bash xbackbone-kde-install.sh
   ```

3. **Restart Spectacle** (and Dolphin). Purpose rescans its plugins on startup.

The installer embeds the plugin, the Python uploader and the icons — nothing else to
download. It writes your credentials to `~/.config/xbackbone/config` (`XBB_URL` /
`XBB_TOKEN`) — the same file the [CLI uploader](/clients/cli-scripts) uses, so both
integrations share one configuration. An existing config is backed up to `config.bak`.

## Use it

- **Spectacle:** take a screenshot, then choose **Export → Share → Upload to XBackBone**.
- **Dolphin:** right-click any file → **Share → Upload to XBackBone**.

The resulting link is copied to your clipboard (when `wl-copy` or `xclip` is available).

## Uninstall

Re-run the installer with `--uninstall`:

```sh
bash xbackbone-kde-install.sh --uninstall
```

This removes the plugin and its icons; your `~/.config/xbackbone/config` is left untouched.

## Troubleshooting

- **The entry doesn't appear:** restart the KDE app so Purpose rescans, and make sure
  `~/.local/share/kpackage/Purpose/xbackbone/contents/code/main.py` is executable.
- **"XBackBone is not configured":** re-run the installer, or create
  `~/.config/xbackbone/config` with `XBB_URL` and `XBB_TOKEN` lines.
- **Debug an upload directly:**
  `~/.local/share/kpackage/Purpose/xbackbone/contents/code/main.py --selftest /path/to/file`
  uploads a file and prints the URL, bypassing the Share menu.
- **Check the logs:** every upload is logged to
  `~/.local/state/xbackbone/kde-plugin.log` (successes, errors, and the target URL).
