# Xerahs

<Badge type="tip" text="Windows" /> <Badge type="tip" text="macOS" /> <Badge type="tip" text="Linux" /> <Badge type="tip" text="Available" />

[Xerahs](https://xerahs.com) is the most advanced screen capture tool for Windows, macOS, and
Linux — ShareX reimagined with modern UI technologies, built from the ground up for cross-platform
performance. Because it is **ShareX-compatible**, XBackBone configures it with the exact same
custom-uploader file: one download and you are sharing straight to your instance.

## Set up

1. Sign in to your XBackBone instance.
2. Open **Integrations** from your profile menu.
3. On the **Xerahs** card, click **Download config** to get your personal `.sxcu` custom-uploader
   file.
4. Import the `.sxcu` into Xerahs — it reads ShareX custom-uploader files — then select XBackBone as
   your active uploader.

That's it. Capture a screenshot or drop a file onto Xerahs and it uploads to your instance, copying
the share link straight to your clipboard.

## What the config contains

The generated `.sxcu` is a standard ShareX custom uploader, pre-filled with your instance URL and a
personal API token. It targets the [`/api/v1/upload`](/clients/api#upload) endpoint and is wired up
as your **image**, **text** and **file** uploader as well as a **URL shortener / sharing service**,
so every destination type flows through XBackBone.

On download, XBackBone mints a token granted the `resource:upload` and `resource:delete`
[abilities](/clients/api#authentication) — enough to upload new resources and remove ones you
created (through the per-upload **deletion URL**).

::: tip Same config as ShareX
Xerahs and [ShareX](/clients/sharex) share the exact same custom-uploader format. A config
downloaded from either card works in both apps — the only difference is the name of the token it
issues, so you can tell them apart under **Profile → Tokens**.
:::

::: warning Keep your token safe
The `.sxcu` file embeds an API token in plain text. Treat it like a password. If it leaks, revoke
the token from **Profile → Tokens** and download a fresh config.
:::

## Troubleshooting

- **Uploads fail with a 401 / "Unauthenticated".** The embedded token was revoked or the config is
  stale — download a fresh `.sxcu` from **Integrations** and re-import it.
- **Links point to the wrong host.** Re-download the config after changing your instance's
  `APP_URL`; the URL is baked into the uploader at generation time.

## See also

- [ShareX](/clients/sharex) — the Windows client Xerahs is compatible with.
- [REST API](/clients/api) — the endpoint Xerahs uploads to, for use from any tool.
