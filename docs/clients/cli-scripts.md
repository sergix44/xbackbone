# CLI

<Badge type="tip" text="Linux" /> <Badge type="tip" text="macOS" /> <Badge type="tip" text="Available" />

A portable shell uploader that hooks straight into your terminal, so you can share any file — or a
screenshot, or piped output — without leaving the command line. You download it from the
**Integrations** area pre-filled with your instance URL and a personal upload token.

## Set up

1. Sign in to your XBackBone instance.
2. Open **Integrations** from your profile menu.
3. On the **CLI** card, click **Download script** to get your personal `xbb`.
4. Make it executable and put it on your `PATH`:

```sh
chmod +x xbb
mkdir -p ~/.local/bin
mv xbb ~/.local/bin/   # ~/.local/bin should be on your PATH
```

That's it — `xbb some-file.png` uploads and prints the link.

The only hard requirement is [`curl`](https://curl.se/). [`jq`](https://jqlang.github.io/jq/) is
used when present for robust JSON parsing (a plain fallback is used otherwise), and the clipboard,
notification and screenshot helpers below are auto-detected and quietly skipped when unavailable.

## Usage

```sh
xbb screenshot.png                 # upload a file (rich output; copies URL, notifies)
xbb a.png b.png c.png              # upload several files at once
xbb --screenshot                   # grab a screen region and upload it
xbb --screenshot-full              # grab the whole screen and upload it
echo "hello" | xbb -               # create a text paste from stdin
git diff | xbb --name patch.diff - # pipe any command's output into a paste
xbb --delete "<deletion-url>"      # delete a previous upload via its deletion URL
xbb --open shot.png                # upload and open the result in your browser
```

### Output modes

The default output is **rich**, human-readable, and (on a desktop) copies the resulting URL to your
clipboard and shows a notification. Two modes are made for pipelines and stay silent otherwise:

```sh
xbb --plain shot.png               # print only the URL, one per line
xbb --plain shot.png | pbcopy      # ...pipe it wherever you like
xbb --json shot.png | jq -r '.data.raw_url'   # raw API JSON for scripting
```

Add `--raw` to make the direct file URL (rather than the preview page) the primary URL used by
`--plain` and the clipboard. Use `--no-copy` / `--no-notify` to opt out of the desktop niceties.

Run `xbb --help` for the full option list. Exit codes are scripting-friendly: `0` success, `2`
usage, `3` missing config, `4` auth, `5` quota exceeded, `6` validation, `7` network/other.

## Configuration

The downloaded script already contains your instance URL and token. You can override them, or
configure a generic copy of the script, through this precedence (first wins):

1. Flags: `--url <base>` and `--token <token>`
2. Environment: `XBB_URL` and `XBB_TOKEN`
3. Config file: `${XDG_CONFIG_HOME:-$HOME/.config}/xbackbone/config`

The config file is simple `KEY=value`:

```sh
XBB_URL=https://files.example.com
XBB_TOKEN=your-token-here
```

::: warning Keep your token safe
The downloaded script embeds an API token in plain text. Treat it like a password. If it leaks,
revoke the token from **Profile → Tokens** and download a fresh script.
:::

## Optional integrations

These are detected automatically; install whichever you use.

- **Clipboard:** `pbcopy` (macOS), `wl-copy` (Wayland), `xclip` or `xsel` (X11).
- **Notifications:** `terminal-notifier` or the built-in `osascript` (macOS), `notify-send` (Linux).
- **Screenshots:** `screencapture` (macOS, built-in); on Linux, `grim`+`slurp` (Wayland),
  `spectacle`, `maim`, `scrot`, `gnome-screenshot`, or ImageMagick's `import`.

## Troubleshooting

- **`No instance URL set` / `No API token set`.** The script isn't configured — download a
  pre-filled copy from **Integrations**, or set `XBB_URL` / `XBB_TOKEN` (see
  [Configuration](#configuration)).
- **Uploads fail with an auth error (exit 4).** The token was revoked or is stale — download a fresh
  script from **Integrations**.
- **Links point to the wrong host.** Re-download the script after changing your instance's
  `APP_URL`; the URL is baked in at generation time.

## See also

- [REST API](/clients/api) — the endpoint this script uploads to, for use from any tool.
- [ShareX](/clients/sharex) — first-class capture integration for Windows.
