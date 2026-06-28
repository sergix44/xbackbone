# Legacy import

Coming from a legacy XBackBone instance? XBackBone can **migrate your users and uploads** and keep
old share links working.

You can run the import in two ways:

- During setup, from the [web installer](/guide/installation#_4-run-the-guided-installer)'s import
  step, or
- Any time afterwards, from the command line.

## What gets migrated

- **Users** are recreated in the new instance.
- **Uploads** are imported as resources into your configured
  [storage backend](/guide/storage).
- **Old links keep working.** Legacy codes are preserved (stored as `legacy_code`), and old
  `/{userCode}/{code}` URLs are **permanently redirected** to the new short URLs — so existing
  embeds and shared links don't break.

## Running the import

From the application root:

```bash
php xbb xbackbone:import
```

Follow the prompts to point the command at your legacy data. Run
`php xbb xbackbone:import --help` to see the available options.

::: tip Do it once, on a quiet instance
Run the import before opening the new instance to users, ideally with a
[queue worker](/guide/installation#_5-background-processing) running so previews for the imported
files are generated in the background.
:::

::: warning Back up first
Imports write users and resources into your new database and storage. Take a backup before you
start, and verify a handful of migrated links afterwards.
:::
