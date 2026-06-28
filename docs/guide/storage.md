# Storage backends

XBackBone stores uploads through [Flysystem](https://flysystem.thephpleague.com/), so you can keep
files on the local disk or push them to a remote service. Four backends are supported:

- **Local disk** (default)
- **Amazon S3** and S3-compatible services
- **FTP**
- **SFTP**

The active backend is selected with `FILESYSTEM_DISK` in your `.env`, and the
[web installer](/guide/installation#_4-run-the-guided-installer) can configure it for you during
setup.

::: info Content-addressed
Whatever the backend, uploads are **content-addressed**: each file is stored under a fingerprint
derived from its contents, so uploading the same file twice stores the bytes only once.
:::

## Local disk

The default. No extra configuration is required:

```dotenv
FILESYSTEM_DISK=local
```

Files live under the application's storage path. Ensure the directory is writable by the web
server user and **not** directly served by your web server.

## Amazon S3 (and compatible)

Works with AWS S3 and any S3-compatible provider (MinIO, Backblaze B2, Cloudflare R2,
DigitalOcean Spaces, Wasabi, …).

```dotenv
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
AWS_USE_PATH_STYLE_ENDPOINT=false
```

For S3-compatible providers, also set a custom endpoint and usually enable path-style addressing:

```dotenv
AWS_ENDPOINT=https://s3.your-provider.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

::: tip
Most non-AWS providers require `AWS_USE_PATH_STYLE_ENDPOINT=true`. If uploads fail with bucket/DNS
errors, that is the first thing to flip.
:::

## FTP

FTP is configured as a Flysystem disk in `config/filesystems.php`:

```php
'ftp' => [
    'driver' => 'ftp',
    'host' => env('FTP_HOST'),
    'username' => env('FTP_USERNAME'),
    'password' => env('FTP_PASSWORD'),

    // Optional
    'port' => env('FTP_PORT', 21),
    'root' => env('FTP_ROOT', ''),
    'passive' => true,
    'ssl' => true,
    'timeout' => 30,
],
```

Then point the app at it:

```dotenv
FILESYSTEM_DISK=ftp
```

## SFTP

SFTP supports both password and private-key authentication:

```php
'sftp' => [
    'driver' => 'sftp',
    'host' => env('SFTP_HOST'),
    'username' => env('SFTP_USERNAME'),

    // Password authentication...
    'password' => env('SFTP_PASSWORD'),

    // ...or key-based authentication
    // 'privateKey' => env('SFTP_PRIVATE_KEY'),
    // 'passphrase' => env('SFTP_PASSPHRASE'),

    'port' => env('SFTP_PORT', 22),
    'root' => env('SFTP_ROOT', ''),
],
```

```dotenv
FILESYSTEM_DISK=sftp
```

## Switching backends

Because resources reference their files by fingerprint, you can change `FILESYSTEM_DISK` at any
time for **new** uploads. Existing files are not migrated automatically — copy them to the new
backend first if you need previously uploaded resources to remain available.

After changing storage settings on a production instance, refresh the cached configuration:

```bash
php xbb optimize
```
