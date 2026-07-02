# REST API

XBackBone exposes a **versioned REST API** (currently **v1**) for uploading and managing resources
programmatically. It's what every client integration uses under the hood, and you can call it
directly from your own code or tooling.

::: tip Per-instance OpenAPI docs
There is no single hosted API reference — the schema is **specific to your instance and version**.
Every instance serves interactive, always-up-to-date OpenAPI documentation at **`/docs/api`**. Browse it on your own instance for the
exact request and response schemas for your version.
:::

## Authentication

Every request is authenticated with a personal access token, sent as a `Bearer` credential:

```
Authorization: Bearer <token>
```

Generate one from **Profile → Tokens**, or let a client integration mint one for you — every
config generated from the **Integrations** page embeds a fresh token pre-scoped to what that
client needs, most commonly the `resource:upload` and `resource:delete` abilities. Tell tokens
apart by name and ability under **Profile → Tokens**, and revoke any of them at any time.

## Upload

```
POST /api/v1/upload
```

A `multipart/form-data` request with one of:

- `file` — the file to upload;
- `data` — a plain-text string, to create a text paste instead of uploading a file.

An optional `name` field overrides the generated filename. The response wraps the created resource
in a `data` object, including `raw_url` (the direct file URL), `preview_ext_url` (the share/preview
page) and `deletion_url` (a pre-authenticated link to delete it).

## Delete

```
DELETE /api/v1/resources/{code}
```

Deletes the resource identified by its short `code` — the same code embedded in its share URL.
Returns `204 No Content`. You must own the resource, or be an administrator.
