# Spectacle (KDE)

<Badge type="tip" text="Linux" /> <Badge type="tip" text="KDE" /> <Badge type="warning" text="In development" />

::: warning In development
The Spectacle / KDE integration is **not available yet**. This page describes how it will work
once released. In the meantime you can upload to XBackBone from any tool using the
[REST API](/clients/api) or the [CLI](/clients/cli-scripts) approach.
:::

[Spectacle](https://apps.kde.org/spectacle/) is KDE's built-in screenshot utility. The XBackBone
integration will provide an upload script with native **KDE** desktop integration, so you can grab
a screenshot and share it in one step.

## What it will do

Once released, you'll download a script from the **Integrations** area, pre-filled with your
instance URL and a personal upload token. Wiring it to a Spectacle "share" action or a custom
shortcut means each capture is uploaded to XBackBone and the share link is copied to your clipboard
automatically.

## In the meantime

Until the dedicated script ships, you can upload from the command line today — see the
[CLI](/clients/cli-scripts) page for a ready-to-adapt `curl` snippet, or the full
[REST API](/clients/api) reference. You can bind that command to a Spectacle script or a KDE custom
shortcut to approximate the final experience.
