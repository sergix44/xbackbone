# Clients

XBackBone is built around screen-capture and file-sharing tools: connect one, and a screenshot or
a dragged file lands on your instance with the short link already on your clipboard.

You set these up from the **Integrations** area of your profile, where XBackBone generates a
ready-to-use configuration pre-filled with your instance URL and a personal upload token — no
manual editing required.

::: warning Keep your token safe
Generated configurations and scripts embed an API token. Treat it like a password, and revoke it
from **Profile → Tokens** if it is ever exposed — then generate a fresh one.
:::

## Capture apps

Install the app, then point it at your server.

| Client | Platforms | Status |
| ------ | --------- | ------ |
| [ShareX](/clients/sharex) | Windows | <Badge type="tip" text="Available" /> |
| [ScreenCloud](/clients/screencloud) | Windows · macOS · Linux | <Badge type="warning" text="In development" /> |

## Desktop & CLI

Drop-in scripts with native desktop or shell integration.

| Client | Platforms | Status |
| ------ | --------- | ------ |
| [Spectacle (KDE)](/clients/spectacle) | Linux · KDE | <Badge type="warning" text="In development" /> |
| [CLI script](/clients/cli-scripts) | Linux · macOS | <Badge type="warning" text="In development" /> |

## Programmatic access

| Interface | Description |
| --------- | ----------- |
| [REST API](/clients/api) | Upload and manage resources from your own code or tooling. Every client above uses it under the hood. |

## Any other tool

Don't see your tool? Any client that can send a `multipart/form-data` file with a bearer token can
upload to XBackBone. Point it at the `/api/v1/upload` endpoint with a token scoped to the
`resource:upload` ability — see the [REST API](/clients/api) reference. Every instance also serves
its own interactive **OpenAPI** documentation (by default at **`/docs/api`**) describing the exact
request and response formats for your version.
