# ScreenCloud

<Badge type="tip" text="Windows" /> <Badge type="tip" text="macOS" /> <Badge type="tip" text="Linux" /> <Badge type="tip" text="Available" />

[ScreenCloud](https://screencloud.net/) is an open-source screen capture and file sharing app for
Windows, macOS and Linux. XBackBone ships a ScreenCloud **uploader plugin** that you install
straight from a link — no manual file editing.

## Set up

1. Sign in to your XBackBone instance.
2. Open **Integrations** from your profile menu.
3. On the **ScreenCloud** card, click **Copy install link**.
4. In ScreenCloud, open the uploader/plugin settings and choose to **install a plugin from a URL**,
   then paste the link. ScreenCloud downloads and installs the XBackBone uploader.
5. Select **XBackBone Uploader** as your active uploader.

That's it. Capture a screenshot in ScreenCloud and it uploads to your instance, with the share link
ready to copy.

## What the link contains

The install link is a **signed URL** that serves a small plugin package (`main.py`, `metadata.xml`,
`settings.ui`, `icon.png`, and a `config.json`) pre-filled with your instance URL and a personal API
token. The plugin uploads to the [`/api/v1/upload`](/clients/api#upload) endpoint with your token as
a `Bearer` credential and reads the resulting share URL from the response.

The token is granted the `resource:upload` and `resource:delete`
[abilities](/clients/api#authentication). You can review or change the token and host later from the
plugin's **Upload Settings** dialog inside ScreenCloud.

::: warning Keep your install link private
The link lets anyone who has it install a plugin carrying an upload token for your account — treat
it like a password. If it leaks, revoke the issued tokens from **Profile → Tokens**; copy a fresh
link to rotate them.
:::

## Troubleshooting

- **Uploads fail with a 401 / "Unauthenticated".** The token was revoked or the config is stale —
  copy a fresh install link from **Integrations** and reinstall the plugin (or update the token in
  the plugin's settings dialog).
- **Links point to the wrong host.** Copy a fresh install link after changing your instance's
  `APP_URL`; the URL is baked into the plugin config at generation time.

## See also

- [ShareX](/clients/sharex) / [Xerahs](/clients/xerahs) — config-file based capture clients.
- [REST API](/clients/api) — the endpoint the plugin uploads to, for use from any tool.
