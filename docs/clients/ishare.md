# ishare

<Badge type="tip" text="macOS" /> <Badge type="tip" text="Available" />

[ishare](https://isharemac.app/) is a clean, unbloated screen capture and file sharing utility for
macOS. It's the macOS counterpart to ShareX: one downloaded file configures ishare to upload
straight to your instance.

## Set up

1. Sign in to your XBackBone instance.
2. Open **Integrations** from your profile menu.
3. On the **ishare** card, click **Download config** to get your personal `.iscu`
   custom-uploader file.
4. **Double-click** the downloaded `.iscu` file — ishare opens it and imports the uploader.
5. In ishare, select the imported uploader as your active **custom uploader**.

That's it. Capture a screenshot or share a file and it uploads to your instance, copying the share
link straight to your clipboard.

::: tip Requires ishare 2.0.0 or newer
The generated config follows the `.iscu` specification introduced in ishare **2.0.0**. On older
versions, update ishare first (a reinstall may be needed after the 2.0.0 breaking changes).
:::

## What the config contains

The generated `.iscu` is a standard ishare custom uploader, pre-filled with your instance URL and a
personal API token. It targets the [`/api/v1/upload`](/clients/api#upload) endpoint as a
`multipart/form-data` upload, and builds the result and deletion links from the response using
ishare's `{{property}}` placeholders — the share link from `{{data.preview_ext_url}}` and the
per-upload deletion link from `{{data.deletion_url}}`.

On download, XBackBone mints a token granted the `resource:upload` and `resource:delete`
[abilities](/clients/api#authentication) — enough to upload new resources and remove ones you
created (ishare exposes the latter through its **deletion URL**).

::: warning Keep your token safe
The `.iscu` file embeds an API token in plain text. Treat it like a password. If it leaks, revoke
the token from **Profile → Tokens** and download a fresh config.
:::

## Troubleshooting

- **Uploads fail with a 401 / "Unauthenticated".** The embedded token was revoked or the config is
  stale — download a fresh `.iscu` from **Integrations** and re-import it.
- **The config won't import.** Make sure you're on ishare **2.0.0 or newer**; the `.iscu` format
  changed in that release.
- **Links point to the wrong host.** Re-download the config after changing your instance's
  `APP_URL`; the URL is baked into the uploader at generation time.

## See also

- [ShareX](/clients/sharex) — the Windows equivalent, using the same upload endpoint.
- [REST API](/clients/api) — the endpoint ishare uploads to, for use from any tool.
