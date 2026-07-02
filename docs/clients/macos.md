# macOS Share sheet

<Badge type="tip" text="macOS" /> <Badge type="tip" text="Available" />

XBackBone integrates with the macOS **Share sheet** through a
[Shortcut](https://support.apple.com/guide/shortcuts-mac/welcome/mac) (Comandi Rapidi). Once
installed, an **Upload to XBackBone** entry appears in:

- the **Share** menu across Finder and most apps (the button with the arrow-in-a-box icon);
- the **Services** menu (right-click → Services);
- the **Shortcuts** app.

Picking it uploads the file to your instance and copies the share link to your clipboard.

The shortcut carries no credentials — it runs the small [`xbb` CLI uploader](/clients/cli-scripts),
so it shares its configuration with the CLI.

## Install

1. Open the **Integrations** page in your XBackBone instance and click **Download installer**
   on the *macOS* card. You get a single, self-contained installer script, pre-filled with your
   instance URL and a personal upload token.
2. Run it:

   ```sh
   bash xbackbone-macos-install.sh
   ```

3. The Shortcuts app opens with the shortcut — click **Add Shortcut**.

The installer embeds the `xbb` uploader and the shortcut — nothing else to download. It writes your
credentials to `~/.config/xbackbone/config` (`XBB_URL` / `XBB_TOKEN`) — the same file the
[CLI uploader](/clients/cli-scripts) uses, so both integrations share one configuration (an existing
config is backed up to `config.bak`). It then signs the shortcut with `shortcuts sign` and opens it
for import.

::: warning Signing and untrusted shortcuts
Importing a shortcut requires it to be signed, which needs an internet connection and iCloud. If
signing fails, the installer falls back to an **unsigned** shortcut: open the Shortcuts app →
**Settings → Advanced**, enable **Allow Untrusted Shortcuts**, then re-run the installer.
:::

## Use it

Share any file — from Finder's **Share** menu, a right-click **→ Share**, or the **Services** menu —
and choose **Upload to XBackBone**. The resulting link is copied to your clipboard and shown in a
notification.

The first time the shortcut runs, macOS asks once to allow it to run a shell script — approve it.

## Uninstall

Re-run the installer with `--uninstall`:

```sh
bash xbackbone-macos-install.sh --uninstall
```

This removes the uploader from `~/Library/Application Support/XBackBone`; your
`~/.config/xbackbone/config` is left untouched. macOS has no command to delete an imported
shortcut, so also delete the **Upload to XBackBone** shortcut in the Shortcuts app.

## Troubleshooting

- **The entry doesn't appear in the Share sheet:** make sure you clicked **Add Shortcut**, and that
  the shortcut is enabled for the Share sheet in its settings.
- **Import is blocked as "untrusted":** enable **Shortcuts → Settings → Advanced → Allow Untrusted
  Shortcuts**, then re-run the installer.
- **"XBackBone is not configured":** re-run the installer, or create `~/.config/xbackbone/config`
  with `XBB_URL` and `XBB_TOKEN` lines.
- **Debug an upload directly:**
  `~/Library/Application\ Support/XBackBone/xbb /path/to/file` uploads a file and prints the URL,
  bypassing the Share sheet.

## See also

- [ishare](/clients/ishare) — a dedicated macOS screen capture app, if you'd rather have a full
  uploader UI than a Share sheet entry.
- [CLI script](/clients/cli-scripts) — the `xbb` uploader this shortcut runs under the hood, and
  the config the two share.
- [REST API](/clients/api) — the endpoint the shortcut uploads to, for use from any tool.
