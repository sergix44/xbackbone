<p align="center">
  <img src="../.github/xbackbone.png" width="320px" alt="XBackBone">
</p>

<h1 align="center">XBackBone Core</h1>

<p align="center">
  The engine behind <a href="https://github.com/SergiX44/XBackBone">XBackBone</a> — a self-hosted
  file and media sharing platform.
</p>

`xbackbone/core` is the heart of XBackBone: a full [Laravel 13](https://laravel.com) application
built with [Livewire 4](https://livewire.laravel.com), packaged as a Composer dependency so it
can be installed, upgraded and downgraded independently from the deployment skeleton (see the
[`app/`](../app) folder in the monorepo root).

## Tech stack

- **PHP** 8.4+ · **Laravel** 13
- **Livewire 4** for the reactive UI, with **Tailwind CSS 4**, **daisyUI** and
  [Mary UI](https://mary-ui.com/) components
- **Laravel Fortify** for authentication, **Sanctum** for API tokens, **Pennant** for feature
  flags
- **Scramble** for auto-generated OpenAPI documentation
- **Spatie Activitylog** for the account and admin activity feed
- **Flysystem** adapters for FTP/SFTP, **php-ffmpeg** and **ImageMagick/GD** for media previews
- **Vite 8** for asset bundling
- Tested with **Pest 4**, analyzed with **Larastan**, formatted with **Pint**

## Application overview

### Domain

The two central models are **`User`** and **`Resource`**. A resource represents anything that
can be shared — an uploaded file (image, video, audio, PDF, text, or generic file), a paste, a
shortened link, or a directory. Resources are **content-addressed**: files are stored under
their content fingerprint so duplicates are stored once, and each resource is exposed through a
short [Sqids](https://sqids.org/)-based code. Resources support public/private visibility,
optional password protection, and expiration.

### Business logic — Actions

Application logic lives in small, context-agnostic building blocks under
[`app/Actions/`](app/Actions), grouped by domain (`Resource`, `User`, `Admin`, `Integration`,
`Import`, `Fortify`). Each action does one thing and is reusable from web requests, the console,
or queued jobs.

### Web UI — Livewire

User-facing pages are Livewire components under [`app/Livewire/`](app/Livewire):

- **Dashboard** — upload files, create pastes and links, manage and search your resources.
- **Preview** — the public viewer for a shared resource, with media players and social embeds.
- **Integrations** — generate uploader configurations for ShareX, ScreenCloud, ishare, Spectacle
  (KDE), the macOS Share sheet, Xerahs, and a CLI script.
- **Profile** — manage account info, API tokens, passkeys, your activity log, data export, and
  account deletion.
- **Admin settings** — sign-up toggle and default theme, user management, statistics, the global
  activity feed, and in-app updates.

### REST API

A versioned API lives under [`routes/api/`](routes/api) (currently **v1**) and is authenticated
with Sanctum bearer tokens scoped by ability (`resource:upload`, `resource:delete`):

| Method   | Endpoint                       | Ability           |
| -------- | ------------------------------ | ----------------- |
| `POST`   | `/api/v1/upload`               | `resource:upload` |
| `DELETE` | `/api/v1/resources/{code}`     | `resource:delete` |

OpenAPI documentation is generated automatically by Scramble.

### Storage backends

Configured in [`config/filesystems.php`](config/filesystems.php): **Local**, **Amazon S3**
(and S3-compatible services), **FTP**, and **SFTP**.

### Authentication

Powered by Laravel Fortify: registration (gated by the `signup` feature flag), email
verification, password reset, profile and password updates, two-factor authentication (TOTP),
and passkeys (WebAuthn).

### Feature flags & settings

Global and per-user settings are managed with Laravel Pennant (see [`app/Features/`](app/Features)):
`SignUp`, `DefaultTheme`, and `AlphabetForIds`.

### Activity log

Notable events — resource uploads and changes, user and token management, passkey changes, and
authentication (logins, registrations, lockouts, failures) — are recorded with
[Spatie Activitylog](https://spatie.be/docs/laravel-activitylog) via the listeners in
[`app/Listeners/`](app/Listeners). The `ActivityLog` Livewire component renders the feed: users
see their own activity under **Profile → Activity**, while admins get a global, searchable feed
filterable by category (`ActivityCategory`) under **Admin → Activity**. Event presentation
(labels, icons, colours) is centralised in `app/Support/ActivityEvent.php`.

### In-app updates

Admins can check for new releases and upgrade the instance from **Admin → Updates**. `CheckForUpdate`
compares the installed `xbackbone/core` version against the latest stable release on Packagist, and
`UpgradeApplication` rewrites the skeleton's Composer requirement and runs the post-upgrade
migrations and cache steps in a detached process. The feature is only available on real skeleton
deployments (`app/Support/Updater.php` gates it on production plus an `APP_ROOT`); see
[`config/updater.php`](config/updater.php).

### Media previews

Previews are generated asynchronously via the `GenerateResourcePreview` job and the generators
in `app/Actions/Resource/Previews/` (raster images, SVG, PDF, and video frames), producing WebP
thumbnails. Tunable via [`config/previews.php`](config/previews.php).

### Web installer

A guided Livewire installer (under [`app/Installer/`](app/Installer)) is served at `/install`.
Until the app is installed, the bootstrap swaps every database-backed driver for a driverless
equivalent and a middleware redirects all traffic to the installer. It configures the
application URL, database, storage, the first admin account, and optional legacy import.

### Legacy import

`php artisan xbackbone:import` (and the installer's import step) migrates users and uploads from
a legacy XBackBone instance. Legacy codes are preserved in `legacy_code`, and old
`/{userCode}/{code}` links are permanently redirected to the new short URLs.

## Development

> **Note:** these instructions are for working on the package in isolation. For a full
> deployment, use the [`app/`](../app) skeleton.

```bash
# Install dependencies
composer install
npm install

# Set up the environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Start the dev servers (Vite + PHP)
npm run dev
php artisan serve
```

Open the app in your browser; on first run you'll be redirected to the `/install` wizard to
create the database schema and the admin account.

### Quality tooling

```bash
php artisan test          # Pest test suite
vendor/bin/pint           # Code style (Laravel Pint)
vendor/bin/phpstan analyse # Static analysis (Larastan)
```

All three must pass before changes are merged.

## License

Apache License 2.0 — see the [LICENSE](../LICENSE) at the repository root.
