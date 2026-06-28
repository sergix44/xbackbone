# REST API

XBackBone exposes a **versioned REST API** (currently **v1**) for uploading and managing resources
programmatically. It's what every client integration uses under the hood, and you can call it
directly from your own code or tooling.

::: tip Per-instance OpenAPI docs
There is no single hosted API reference — the schema is **specific to your instance and version**.
Every instance serves interactive, always-up-to-date OpenAPI documentation at **`/docs/api`**. Browse it on your own instance for the
exact request and response schemas for your version.
:::
