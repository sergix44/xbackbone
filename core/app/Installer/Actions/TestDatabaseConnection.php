<?php

namespace XBB\Installer\Actions;

use XBB\Console\Commands\ImportLegacyCommand;
use XBB\Installer\Support\DatabaseConfig;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Verifies a candidate database configuration by opening a throwaway
 * connection, mirroring {@see ImportLegacyCommand}.
 */
class TestDatabaseConnection
{
    private const PROBE = 'installer_probe';

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, message: string}
     */
    public function __invoke(array $payload): array
    {
        if (($payload['driver'] ?? null) === 'sqlite') {
            $path = (string) ($payload['sqlitePath'] ?? '');
            $directory = dirname($path);

            if (! is_dir($directory) || ! is_writable($directory)) {
                return ['ok' => false, 'message' => __('The SQLite directory is not writable: :dir', ['dir' => $directory])];
            }

            // Laravel's SQLite connector refuses to connect to a missing file,
            // so create it now — this is the path the app will use anyway.
            if (! is_file($path)) {
                @touch($path);
            }
        }

        try {
            config(['database.connections.'.self::PROBE => DatabaseConfig::connection($payload)]);
            DB::purge(self::PROBE);
            DB::connection(self::PROBE)->getPdo();
            DB::connection(self::PROBE)->select('select 1');

            return ['ok' => true, 'message' => __('Connection successful.')];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        } finally {
            DB::purge(self::PROBE);
        }
    }
}
