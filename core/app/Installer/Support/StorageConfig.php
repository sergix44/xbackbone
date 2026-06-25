<?php

namespace App\Installer\Support;

/**
 * Translates the wizard's storage payload into a Laravel disk definition
 * (for live probing via Storage::build) and the matching `.env` keys.
 */
final class StorageConfig
{
    /**
     * Build an on-the-fly disk definition. `throw` is enabled so the storage
     * probe surfaces failures instead of silently swallowing them.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function disk(array $payload): array
    {
        $driver = (string) ($payload['driver'] ?? 'local');

        return match ($driver) {
            's3' => [
                'driver' => 's3',
                'key' => (string) ($payload['key'] ?? ''),
                'secret' => (string) ($payload['secret'] ?? ''),
                'region' => (string) ($payload['region'] ?? ''),
                'bucket' => (string) ($payload['bucket'] ?? ''),
                'endpoint' => ($payload['endpoint'] ?? '') ?: null,
                'use_path_style_endpoint' => (bool) ($payload['usePathStyle'] ?? false),
                'throw' => true,
            ],
            'ftp' => [
                'driver' => 'ftp',
                'host' => (string) ($payload['host'] ?? ''),
                'port' => (int) ($payload['port'] ?? 21),
                'username' => (string) ($payload['username'] ?? ''),
                'password' => (string) ($payload['password'] ?? ''),
                'root' => (string) ($payload['root'] ?? ''),
                'throw' => true,
            ],
            'sftp' => [
                'driver' => 'sftp',
                'host' => (string) ($payload['host'] ?? ''),
                'port' => (int) ($payload['port'] ?? 22),
                'username' => (string) ($payload['username'] ?? ''),
                'password' => (string) ($payload['password'] ?? ''),
                'root' => (string) ($payload['root'] ?? ''),
                'throw' => true,
            ],
            default => [
                'driver' => 'local',
                'root' => (string) ($payload['root'] ?? storage_path('app')),
                'throw' => true,
            ],
        };
    }

    /**
     * Build the `.env` keys for the chosen disk.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, scalar|null>
     */
    public static function env(array $payload): array
    {
        $driver = (string) ($payload['driver'] ?? 'local');

        return match ($driver) {
            's3' => [
                'FILESYSTEM_DISK' => 's3',
                'AWS_ACCESS_KEY_ID' => (string) ($payload['key'] ?? ''),
                'AWS_SECRET_ACCESS_KEY' => (string) ($payload['secret'] ?? ''),
                'AWS_DEFAULT_REGION' => (string) ($payload['region'] ?? ''),
                'AWS_BUCKET' => (string) ($payload['bucket'] ?? ''),
                'AWS_ENDPOINT' => (string) ($payload['endpoint'] ?? ''),
                'AWS_USE_PATH_STYLE_ENDPOINT' => (bool) ($payload['usePathStyle'] ?? false),
            ],
            'ftp' => [
                'FILESYSTEM_DISK' => 'ftp',
                'FTP_HOST' => (string) ($payload['host'] ?? ''),
                'FTP_PORT' => (int) ($payload['port'] ?? 21),
                'FTP_USERNAME' => (string) ($payload['username'] ?? ''),
                'FTP_PASSWORD' => (string) ($payload['password'] ?? ''),
                'FTP_ROOT' => (string) ($payload['root'] ?? ''),
            ],
            'sftp' => [
                'FILESYSTEM_DISK' => 'sftp',
                'SFTP_HOST' => (string) ($payload['host'] ?? ''),
                'SFTP_PORT' => (int) ($payload['port'] ?? 22),
                'SFTP_USERNAME' => (string) ($payload['username'] ?? ''),
                'SFTP_PASSWORD' => (string) ($payload['password'] ?? ''),
                'SFTP_ROOT' => (string) ($payload['root'] ?? ''),
            ],
            default => [
                'FILESYSTEM_DISK' => 'local',
            ],
        };
    }
}
