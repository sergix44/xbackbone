<p align="center">
  <img src=".github/xbackbone.png" width="350px" alt="XBackBone">
</p>

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-Apache--2.0-blue" alt="License"></a>
</p>

**XBackBone** is a simple, self-hosted, lightweight file and media sharing platform with
first-class [ShareX](https://getsharex.com/) support. Upload images, GIFs, video, audio, PDFs,
code and arbitrary files, then share them through clean short links with rich social embeds.
It ships with a modern web UI, multi-user management, a versioned REST API, and pluggable
storage backends.

This is the next-generation XBackBone, rebuilt from the ground up on **Laravel 13** and
**Livewire 4**.

## Features

- **One-click integrations** — generate ready-to-use uploader configs for ShareX, ScreenCloud,
  ishare, Spectacle (KDE), the macOS Share sheet, Xerahs, and a CLI script, all pre-filled with
  your instance URL and a personal token.
- **Wide media support** — inline previews for images, video, audio (waveform), PDFs, and
  syntax-highlighted code, plus pastes and link shortening.
- **Multiple storage backends** — Local disk, Amazon S3 (and S3-compatible), FTP, and SFTP.
- **Content-addressed storage** — uploads are de-duplicated by content fingerprint.
- **Private & public uploads** — per-resource visibility, optional password protection and
  expiration.
- **REST API** — versioned API with token authentication and auto-generated OpenAPI docs.
- **Modern authentication** — registration, email verification, password reset, two-factor
  authentication (TOTP), and passkeys (WebAuthn).
- **User management** — admin roles, per-user disk quotas, and usage statistics.
- **Activity log** — a searchable, filterable timeline of account and admin activity, for both
  your own actions and (for admins) the whole instance.
- **Feature flags** — toggle sign-ups, default theme and more without redeploying.
- **Theming** — switchable daisyUI themes, localization-ready UI.
- **Guided web installer** — set up the database, storage, and admin account from the browser.
- **In-app updates** — admins can check for new releases and upgrade the instance from the
  browser, with no shell access required.
- **Legacy import** — migrate users and uploads from a legacy XBackBone instance, with old
  links transparently redirected.

## Architecture

This repository is a monorepo composed of two parts:

| Directory | Description |
| --------- | ----------- |
| [`core/`](core) | The full XBackBone application — a Laravel + Livewire app that implements all the logic. It is published as the `xbackbone/core` Composer package. |
| [`app/`](app) | A minimal installation skeleton (config, bootstrap and public entrypoint) that pulls `xbackbone/core` in as a Composer dependency. |

The skeleton boots the core package and remaps its public, storage and environment paths to
the skeleton root. Keeping the application logic in a versioned package means an instance can
be **upgraded or downgraded by simply changing the required version of `xbackbone/core`**,
without touching the rest of the deployment.

See [`core/README.md`](core/README.md) for the application's technical details and the
development setup.

## Getting started

Full installation, configuration and usage instructions are available in the documentation.

## Documentation

[XBackBone Documentation](https://sergix44.github.io/XBackBone/)

## Contributing

Contributions are welcome! Please read the [Code of Conduct](CODE_OF_CONDUCT.md) before
opening an issue or pull request.

## Security

If you discover a security vulnerability within XBackBone, please email
**sergio@brighenti.me** instead of using the public issue tracker. See
[SECURITY.md](SECURITY.md) for details. All reports are addressed promptly.

## License

XBackBone is open-source software licensed under the
[Apache License 2.0](LICENSE).
