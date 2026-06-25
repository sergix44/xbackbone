<?php

namespace App\Installer\Actions;

use App\Actions\Admin\CreateUser;
use App\Installer\Exceptions\InstallationException;
use App\Installer\Support\DatabaseConfig;
use App\Installer\Support\EnvWriter;
use App\Installer\Support\InstallationState;
use App\Installer\Support\StorageConfig;
use App\Models\Properties\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Applies the wizard's choices in an ordered, idempotent sequence. The
 * installation marker is written last, so any earlier failure leaves the app
 * uninstalled and the wizard re-runnable.
 */
class FinalizeInstallation
{
    public function __construct(private readonly CreateUser $createUser) {}

    /**
     * @param  array{
     *     appUrl: string,
     *     database: array<string, mixed>,
     *     storage: array<string, mixed>,
     *     admin: array{name: string, email: string, password: string},
     *     import: array<string, mixed>|null
     * }  $payload
     */
    public function __invoke(array $payload): User
    {
        $env = EnvWriter::forApp();

        // 1. Probe the database before persisting anything irreversible.
        $this->connectDatabase($payload['database']);

        // 2. Persist app URL + database + storage configuration first; a crash
        //    after this still leaves a re-runnable, correctly configured app.
        $env->set([
            'APP_URL' => $payload['appUrl'],
            ...DatabaseConfig::env($payload['database']),
            ...StorageConfig::env($payload['storage']),
        ]);

        // 3. Migrate against the freshly configured connection.
        $this->migrate();

        // 4. Create (or reuse) the first administrator.
        $admin = $this->createAdmin($payload['admin']);

        // 5. Optional legacy import (non-fatal: it is idempotent and re-runnable).
        if ($payload['import'] !== null) {
            $this->runImport($payload['import'], $admin);
        }

        // 6. Restore database-backed runtime services and mark installed
        //    (effective on the next request).
        $env->set([
            'APP_INSTALLED' => true,
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'database',
        ]);

        // 7. Drop cached config/routes/views so the new env is honoured.
        $this->clearCaches();

        // 8. Point of no return.
        InstallationState::lock();

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $database
     */
    private function connectDatabase(array $database): void
    {
        try {
            if (($database['driver'] ?? null) === 'sqlite') {
                $path = (string) ($database['sqlitePath'] ?? '');
                $directory = dirname($path);

                if (! is_dir($directory)) {
                    @mkdir($directory, 0775, true);
                }

                if (! is_file($path)) {
                    @touch($path);
                }
            }

            config([
                'database.connections.install' => DatabaseConfig::connection($database),
                'database.default' => 'install',
            ]);

            DB::purge('install');
            DB::reconnect('install');
            DB::connection('install')->getPdo();
        } catch (Throwable $e) {
            throw InstallationException::atStep(1, __('Could not connect to the database: :error', ['error' => $e->getMessage()]), $e);
        }
    }

    private function migrate(): void
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            throw InstallationException::atStep(1, __('Database migration failed: :error', ['error' => $e->getMessage()]), $e);
        }
    }

    /**
     * @param  array{name: string, email: string, password: string}  $admin
     */
    private function createAdmin(array $admin): User
    {
        try {
            $existing = User::query()->where('email', $admin['email'])->first();

            if ($existing !== null) {
                return $existing;
            }

            return ($this->createUser)([
                'name' => $admin['name'],
                'email' => $admin['email'],
                'password' => $admin['password'],
                'is_admin' => true,
                'status' => UserStatus::ENABLED,
                'quota' => -1,
            ]);
        } catch (Throwable $e) {
            throw InstallationException::atStep(3, __('Could not create the administrator account: :error', ['error' => $e->getMessage()]), $e);
        }
    }

    /**
     * @param  array<string, mixed>  $import
     */
    private function runImport(array $import, User $admin): void
    {
        @set_time_limit(0);

        $options = [
            '--driver' => $import['driver'],
            '--storage-path' => $import['storagePath'],
            '--orphans' => $import['orphans'],
            '--admin-id' => $admin->id,
        ];

        if ($import['driver'] === 'sqlite') {
            $options['--db-file'] = $import['file'];
        } else {
            $options['--db-host'] = $import['host'];
            $options['--db-port'] = $import['port'];
            $options['--db-database'] = $import['database'];
            $options['--db-username'] = $import['username'];
            $options['--db-password'] = $import['password'];
        }

        if (! empty($import['withPreviews'])) {
            $options['--with-previews'] = true;
        }

        Artisan::call('xbackbone:import', $options);
    }

    private function clearCaches(): void
    {
        foreach (['config:clear', 'route:clear', 'view:clear'] as $command) {
            try {
                Artisan::call($command);
            } catch (Throwable) {
                // Best effort: a failure here does not invalidate the install.
            }
        }
    }
}
