# Configuration

XBackBone is configured at two levels:

- **Environment** (`.env`) — infrastructure settings: URL, database, mail, cache, queue and
  storage drivers. Applied at boot.
- **Runtime settings** — application behaviour managed from the admin **Settings** page,
  changeable without a redeploy.

## Environment reference

### Application

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://files.example.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=UTC
```

| Variable | Description |
| -------- | ----------- |
| `APP_URL` | Public base URL. Used to build share links — set it correctly. |
| `APP_ENV` | Use `production` for live instances. |
| `APP_DEBUG` | **Must** be `false` in production. |
| `APP_LOCALE` | Default UI language. |
| `APP_TIMEZONE` | Server timezone for timestamps. |

### Database

SQLite is the default and requires no server. To use MySQL/MariaDB or PostgreSQL, set the
connection details:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=xbackbone
DB_USERNAME=xbackbone
DB_PASSWORD=secret
```

::: tip
The [web installer](/guide/installation#_4-run-the-guided-installer) writes these values for you
and runs the migrations automatically.
:::

### Storage

```dotenv
FILESYSTEM_DISK=local
```

The default `local` disk stores uploads on the server's filesystem. To use S3, FTP or SFTP, see
the dedicated [Storage backends](/guide/storage) page.

### Cache, queue and sessions

```dotenv
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

These default to the database, which works everywhere with no extra services. For higher
throughput, switch them to Redis:

```dotenv
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

::: warning
`QUEUE_CONNECTION` drives media-preview generation. Whichever driver you choose, make sure a
[queue worker](/guide/installation#_5-background-processing) is running.
:::

### Mail

Email is used for verification and password resets. Configure an SMTP transport in production:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=postmaster@example.com
MAIL_PASSWORD=secret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="files@example.com"
MAIL_FROM_NAME="XBackBone"
```

### Activity log

XBackBone records notable events (uploads, user and token changes, logins, …) to an activity feed.
It is enabled by default and needs no configuration.

```dotenv
ACTIVITY_LOGGER_ENABLED=true
```

| Variable | Description |
| -------- | ----------- |
| `ACTIVITY_LOGGER_ENABLED` | Set to `false` to stop recording activity entirely. |

Entries older than a year are pruned when you run `php xbb activitylog:clean` (schedule it if you
want automatic housekeeping).

### Updates

The [in-app updater](/guide/upgrading#from-the-admin-area) auto-detects Composer. Only set this if
it lives at a non-standard path:

```dotenv
XBB_COMPOSER_BINARY=/usr/local/bin/composer
```

## Runtime settings

The admin **Settings** page controls behaviour you can change live, including:

- **Sign-ups** — allow or block public registration.
- **Default theme** — the daisyUI theme new visitors get.
- **User management** — roles, per-user disk quotas and usage statistics.
- **Activity** — a searchable, category-filterable feed of instance-wide activity. Each user also
  sees their own activity under their profile.
- **Updates** — check for new releases and upgrade the instance from the browser. See
  [Upgrading](/guide/upgrading#from-the-admin-area).

Some of these are evaluated per user as well as globally — for example a user can pick their own
theme, falling back to the global default when they haven't.

## Applying changes

After editing `.env` on a production instance, refresh the cached configuration:

```bash
php xbb optimize
```

Runtime settings changed from the admin area take effect immediately — no command needed.
