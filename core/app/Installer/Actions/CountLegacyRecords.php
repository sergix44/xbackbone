<?php

namespace App\Installer\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Counts the users and uploads in a legacy XBackBone database, giving the user
 * a preview before committing to an import.
 */
class CountLegacyRecords
{
    private const PROBE = 'legacy_probe';

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, users: int, uploads: int, message: string}
     */
    public function __invoke(array $payload): array
    {
        try {
            config(['database.connections.'.self::PROBE => $this->connection($payload)]);
            DB::purge(self::PROBE);
            $connection = DB::connection(self::PROBE);
            $connection->getPdo();

            return [
                'ok' => true,
                'users' => $connection->table('users')->count(),
                'uploads' => $connection->table('uploads')->count(),
                'message' => '',
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'users' => 0, 'uploads' => 0, 'message' => $e->getMessage()];
        } finally {
            DB::purge(self::PROBE);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function connection(array $payload): array
    {
        return ($payload['driver'] ?? 'mysql') === 'sqlite'
            ? [
                'driver' => 'sqlite',
                'database' => (string) ($payload['file'] ?? ''),
                'prefix' => '',
                'foreign_key_constraints' => false,
            ]
            : [
                'driver' => 'mysql',
                'host' => (string) ($payload['host'] ?? '127.0.0.1'),
                'port' => (string) ($payload['port'] ?? 3306),
                'database' => (string) ($payload['database'] ?? ''),
                'username' => (string) ($payload['username'] ?? ''),
                'password' => (string) ($payload['password'] ?? ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
            ];
    }
}
