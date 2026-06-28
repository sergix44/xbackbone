# ScreenCloud

<Badge type="tip" text="Windows" /> <Badge type="tip" text="macOS" /> <Badge type="tip" text="Linux" /> <Badge type="warning" text="In development" />

::: warning In development
The ScreenCloud integration is **not available yet**. This page describes how it will work once
released. In the meantime you can upload to XBackBone from any tool using the
[REST API](/clients/api), or — on Windows — with [ShareX](/clients/sharex).
:::

[ScreenCloud](https://screencloud.net/) is an open-source screen capture and file sharing app
available across every desktop platform — Windows, macOS and Linux.

## What it will do

Once released, you'll install ScreenCloud and connect it to your instance from the **Integrations**
area of your profile, the same way as the other clients. After that, capturing a screenshot uploads
it to XBackBone and copies the share link to your clipboard.

## In the meantime

Until the dedicated integration ships, you can still upload to your instance:

- **Windows** — use [ShareX](/clients/sharex), which has first-class support today.
- **Any platform** — call the [REST API](/clients/api) directly, for example with `curl`:

  ```bash
  curl -X POST https://files.example.com/api/v1/upload \
    -H "Authorization: Bearer <your-token>" \
    -H "Accept: application/json" \
    -F "file=@/path/to/screenshot.png"
  ```
