# ShareX

<Badge type="tip" text="Windows" /> <Badge type="tip" text="Available" />

[ShareX](https://getsharex.com/) is a free, open-source screen capture, file sharing and
productivity tool for Windows. XBackBone has **first-class support** for it: one downloaded file
configures ShareX to upload straight to your instance.

## Set up

1. Sign in to your XBackBone instance.
2. Open **Integrations** from your profile menu.
3. On the **ShareX** card, click **Download config** to get your personal `.sxcu`
   custom-uploader file.
4. **Double-click** the downloaded `.sxcu` file to import it — or, in ShareX, go to
   **Destinations → Custom uploader settings → Import → From file**.
5. In ShareX, set XBackBone as the active uploader under **Destinations**.

That's it. Capture a screenshot or drag a file onto ShareX and it uploads to your instance, copying
the share link straight to your clipboard.

## What the config contains

The generated `.sxcu` is a standard ShareX custom uploader, pre-filled with your instance URL and a
personal API token. It targets the [`/api/v1/upload`](/clients/api#upload) endpoint and is wired up
as your **image**, **text** and **file** uploader as well as a **URL shortener / sharing service**,
so every ShareX destination type flows through XBackBone.

On download, XBackBone mints a token granted the `resource:upload` and `resource:delete`
[abilities](/clients/api#authentication) — enough to upload new resources and remove ones you
created (ShareX exposes the latter through its per-upload **deletion URL**).

::: warning Keep your token safe
The `.sxcu` file embeds an API token in plain text. Treat it like a password. If it leaks, revoke
the token from **Profile → Tokens** and download a fresh config.
:::

## Troubleshooting

- **Uploads fail with a 401 / "Unauthenticated".** The embedded token was revoked or the config is
  stale — download a fresh `.sxcu` from **Integrations** and re-import it.
- **The wrong destination is used.** Make sure XBackBone is selected as the active uploader for the
  relevant type under **Destinations**.
- **Links point to the wrong host.** Re-download the config after changing your instance's
  `APP_URL`; the URL is baked into the uploader at generation time.

## See also

- [REST API](/clients/api) — the endpoint ShareX uploads to, for use from any tool.
