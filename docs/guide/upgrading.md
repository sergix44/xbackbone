# Upgrading

XBackBone's application logic lives in the versioned `xbackbone/core` Composer package, while your
deployment is the thin `app` skeleton. Upgrading (or rolling back) is therefore a matter of
changing **one version constraint** and updating dependencies.

::: tip Why this is nice
Because the core is a package, your configuration, environment and customisations in the skeleton
are untouched by an upgrade. You can also **downgrade** the same way if a release misbehaves.
:::

## Before you start

- Take a **backup** of your database and your uploaded files.
- Note your **current version** so you can roll back: `composer show xbackbone/core`.
- Put the site into maintenance mode if you want zero in-flight writes:

```bash
php xbb down
```

## Upgrade

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
