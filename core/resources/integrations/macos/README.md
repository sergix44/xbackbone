# XBackBone — macOS "Share" (Shortcut) integration

Adds an **Upload to XBackBone** entry to the macOS **Share sheet** (and the Services menu and
the Shortcuts app), so you can send a file straight to your XBackBone instance from Finder's
Share menu, right-click → Share, and any app that offers the system Share sheet.

It is a **Shortcut** (Comandi Rapidi) whose `WFWorkflowTypes` contains `ActionExtension` — the
flag that makes a shortcut appear in the Share sheet. The shortcut itself carries no
credentials: it runs a single *Run Shell Script* action that calls the bundled
[`xbb` CLI uploader](../xbb), which reads the shared configuration.

## Install

From your instance's **Integrations** page, download the installer (a single shell script,
pre-filled with your instance URL and a personal upload token) and run it:

```sh
bash xbackbone-macos-install.sh
```

Then, in the Shortcuts app, click **Add Shortcut**.

The installer is self-contained — it embeds the `xbb` uploader and the shortcut — and:

- writes your credentials to `~/.config/xbackbone/config` (`XBB_URL` / `XBB_TOKEN`) — the same
  file the [`xbb` CLI uploader](../xbb) uses, so the two integrations share one configuration
  (an existing config is backed up to `config.bak`);
- installs `xbb` and a small `xbb-share` bridge into `~/Library/Application Support/XBackBone/`;
- writes the shortcut, converts it to a binary plist, **signs** it with `shortcuts sign` and
  opens it for import. If signing fails (offline / not signed into iCloud) it falls back to an
  unsigned shortcut and prints how to enable *Settings → Advanced → Allow Untrusted Shortcuts*.

Uninstall with `bash xbackbone-macos-install.sh --uninstall` (your config is left untouched).
macOS has no CLI to delete an imported shortcut, so the installer asks you to remove the
shortcut manually in the Shortcuts app.

## How it works

The Share sheet hands the selected items to the shortcut, which runs `xbb-share` as a shell
script. `xbb-share` collects the shared items (as arguments or from stdin) and calls `xbb`,
which uploads each file to `POST {XBB_URL}/api/v1/upload` with a `Bearer` token, copies the
resulting share link to the clipboard (`pbcopy`) and shows a notification (`osascript` /
`terminal-notifier`). Upload failures are surfaced as a notification, since a shortcut has no
visible stderr. The first time the shortcut runs its shell action, macOS asks once to allow it
to run scripts.

## Layout of this directory

These are the *sources* of the integration. The downloadable installer is assembled from them
by `App\Actions\Integration\GenerateMacosShortcut`, which fills `installer.sh.stub` with the
instance name, a fresh token, the base64-encoded `xbb` uploader, and the base64-encoded shortcut
plist (with the instance name substituted).

- `installer.sh.stub` — the self-contained installer template.
- `shortcut.plist.stub` — the shortcut definition (`@@SHORTCUT_NAME@@` is replaced at generation
  time; converted to a binary plist and signed at install time).

> **Maintainer note:** the `.shortcut` format is undocumented/reverse-engineered. Before
> shipping, build this shortcut once in the Shortcuts app on a Mac ("Receive files/images from
> the Share Sheet" → *Run Shell Script*), confirm it appears in the Share sheet, then decompile
> it to verify the exact `is.workflow.actions.runshellscript` parameter keys and the
> `WFWorkflowInputContentItemClasses` values used in `shortcut.plist.stub`.

## Troubleshooting

- **The entry doesn't show up in the Share sheet:** make sure you clicked **Add Shortcut**, and
  that the shortcut is enabled for the Share sheet in its settings (it ships with
  `WFWorkflowTypes = ActionExtension`).
- **"Untrusted shortcut" on import:** enable *Shortcuts app → Settings → Advanced → Allow
  Untrusted Shortcuts*, then re-run the installer (or re-open the `.shortcut`).
- **"XBackBone is not configured":** re-run the installer, or create
  `~/.config/xbackbone/config` with `XBB_URL` and `XBB_TOKEN` lines.
- **Debug an upload directly:**
  `~/Library/Application\ Support/XBackBone/xbb /path/to/file` uploads a file and prints the URL,
  bypassing the Share sheet.
