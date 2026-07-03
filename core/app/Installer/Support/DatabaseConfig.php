<?php

namespace XBB\Installer\Support;

/**
 * Translates the wizard's database payload into a Laravel connection config
 * and the matching set of `.env` keys.
 */
final class DatabaseConfig
{
    /**
     * Build a Laravel database connection definition.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function connection(array $payload): array
    {
        $driver = (string) ($payload['driver'] ?? 'sqlite');

        if ($driver === 'sqlite') {
            return [
                'driver' => 'sqlite',
                'database' => (string) ($payload['sqlitePath'] ?? ''),
                'prefix' => '',
                'foreign_key_constraints' => true,
            ];
        }

        $base = [
            'driver' => $driver,
            'host' => (string) ($payload['host'] ?? '127.0.0.1'),
            'port' => (string) ($payload['port'] ?? ''),
            'database' => (string) ($payload['database'] ?? ''),
            'username' => (string) ($payload['username'] ?? ''),
            'password' => (string) ($payload['password'] ?? ''),
            'prefix' => '',
        ];

        if ($driver === 'pgsql') {
            return [...$base, 'charset' => 'utf8', 'search_path' => 'public', 'sslmode' => 'prefer'];
        }

        return [...$base, 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'strict' => true];
    }

    /**
     * Build the `.env` keys for the chosen database.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, scalar|null>
     */
    public static function env(array $payload): array
    {
        $driver = (string) ($payload['driver'] ?? 'sqlite');

        if ($driver === 'sqlite') {
            return [
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => (string) ($payload['sqlitePath'] ?? ''),
            ];
        }

        return [
            'DB_CONNECTION' => $driver,
            'DB_HOST' => (string) ($payload['host'] ?? ''),
            'DB_PORT' => (int) ($payload['port'] ?? 0),
            'DB_DATABASE' => (string) ($payload['database'] ?? ''),
            'DB_USERNAME' => (string) ($payload['username'] ?? ''),
            'DB_PASSWORD' => (string) ($payload['password'] ?? ''),
        ];
    }
}
