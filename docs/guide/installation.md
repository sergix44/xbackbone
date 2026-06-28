# Installation

XBackBone ships a **guided web installer** that configures the database, storage and the first
admin account from your browser. This page covers the server requirements, getting the
application files in place, and finishing the setup.

::: tip Deployment model
You deploy the **`app` skeleton**, which pulls in the `xbackbone/core` package via Composer. You
do not clone the core application directly for a production install — see
[Upgrading](/guide/upgrading) for how versioning works.
:::

## Requirements

- **PHP 8.4 or newer** with the typical Laravel extensions:
  `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`,
  `session`, `tokenizer`, `xml`, plus `gd` (or `imagick`) and `exif` for image previews.
- **Composer 2**.
- A **web server** (nginx, Apache, Caddy, …) or PHP-FPM behind one.
- A **database**: SQLite (default), MySQL/MariaDB, or PostgreSQL.
- *Optional:* **ffmpeg** for video thumbnails, and **Redis** if you prefer it for cache/queues.

## 1. Create the project

Create a new instance with Composer. This downloads the `app` skeleton, pulls in
`xbackbone/core` and the rest of the dependencies, copies `.env.example` to `.env` and generates
an application key for you:

```bash
composer create-project xbackbone/app xbackbone
```

This leaves you with a ready-to-configure instance in the `xbackbone/` directory.

::: tip The `xbb` console
The skeleton ships a console binary named **`xbb`** (the XBackBone equivalent of Laravel's
`artisan`). Run management commands with `php xbb <command>` from the project root.
:::

## 2. Configure the environment

The `.env` file already exists with an application key. At minimum, set your public URL:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://files.example.com
```

You can leave the database and storage settings untouched for now — the web installer will write
them for you. See [Configuration](/guide/configuration) for the full reference.

## 3. Point your web server at `public/`

The document root **must** be the `public/` directory. A minimal nginx example:

```nginx
server {
    listen 80;
    server_name files.example.com;
    root /var/www/xbackbone/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Make sure the web server user can write to `storage/` and `bootstrap/cache/`.

## 4. Run the guided installer

Open your instance in a browser. Until the application is installed, all traffic is redirected to
the installer at **`/install`**. The wizard walks you through:

1. **Application URL** and basic settings.
2. **Database** connection (SQLite / MySQL / PostgreSQL) — migrations run automatically.
3. **Storage backend** — local disk, S3, FTP or SFTP (see [Storage backends](/guide/storage)).
4. **Admin account** — your first user.
5. *Optional:* **Legacy import** from an existing XBackBone instance
   (see [Legacy import](/guide/legacy-import)).

Once finished, the installer locks itself and the app becomes available.

## 5. Background processing

Media previews and other slow work run on the queue, and some maintenance runs on a schedule.
For production, run a queue worker and the scheduler.

Run a queue worker (with a supervisor such as `systemd` or Supervisor):

```bash
php xbb queue:work --tries=3
```

Add the Laravel scheduler to cron:

```txt
* * * * * cd /var/www/xbackbone && php xbb schedule:run >> /dev/null 2>&1
```

::: warning Previews need a worker
Thumbnails are generated asynchronously by a queued job. If no worker is running, uploads still
succeed but previews will not appear until the job is processed.
:::

## 6. Cache for production

After deploying (and after each upgrade), cache the framework files:

```bash
php xbb optimize
```

Your instance is now ready. Next: [Configuration](/guide/configuration).
