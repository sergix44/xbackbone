# Upgrading

XBackBone's application logic lives in the versioned `xbackbone/core` Composer package, while your
deployment is the thin `app` skeleton. Upgrading (or rolling back) is therefore a matter of
changing **one version constraint** and updating dependencies.

::: tip Why this is nice
Because the core is a package, your configuration, environment and customisations in the skeleton
are untouched by an upgrade. You can also **downgrade** the same way if a release misbehaves.
:::

## From the admin area

The easiest way to upgrade is from the browser — no shell access required. As an admin, open
**Settings → Updates**:

1. XBackBone shows your **current version** and, if one exists, the **latest release**. Use
   **Check now** to bypass the cache and re-query Packagist.
2. Click **Upgrade** to update to the latest version. XBackBone rewrites the `xbackbone/core`
   requirement in the skeleton's `composer.json`, runs Composer, and then applies the new
   migrations and rebuilds the caches for you. Progress is streamed live on the page.

::: warning Requirements
The in-app updater only appears on a real deployment installed through the `app` skeleton and
running in production. The web server user must be able to run Composer and write to the skeleton
(`vendor/`, `composer.json`, `composer.lock`). If Composer lives at a non-standard path, set
`XBB_COMPOSER_BINARY` in your `.env`. When these conditions aren't met, use the manual process
below.
:::

Take a **backup** first (see below) — an in-app upgrade runs the same migrations as the manual
process, and those are not always reversible.

## Manual upgrade

Prefer the command line, or the in-app updater isn't available? Upgrade by hand instead.

### Before you start

- Take a **backup** of your database and your uploaded files.
- Note your **current version** so you can roll back: `composer show xbackbone/core`.
- Put the site into maintenance mode if you want zero in-flight writes:

```bash
php xbb down
```

### Steps

1. Bump the constraint in the skeleton's `composer.json`, e.g.:

   ```json
   {
     "require": {
       "xbackbone/core": "^1.2"
     }
   }
   ```

2. Update dependencies:

   ```bash
   composer update xbackbone/core --with-all-dependencies
   ```

3. Run any new database migrations:

   ```bash
   php xbb migrate --force
   ```

4. Rebuild the production caches:

   ```bash
   php xbb optimize
   ```

5. Restart your queue workers so they pick up the new code:

   ```bash
   php xbb queue:restart
   ```

6. Bring the site back up:

   ```bash
   php xbb up
   ```

## Downgrade

Point the constraint at the older version and update again:

```bash
composer require xbackbone/core:^1.1 --with-all-dependencies
php xbb optimize
php xbb queue:restart
```

::: warning Migrations are not always reversible
If the newer version added migrations that changed data, rolling the package back does not
automatically undo them. Restore your database backup if a downgrade needs the old schema.
:::
