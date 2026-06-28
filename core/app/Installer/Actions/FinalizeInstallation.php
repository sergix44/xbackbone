<?php

namespace App\Installer\Actions;

use App\Actions\Admin\CreateUser;
use App\Installer\Exceptions\InstallationException;
use App\Installer\Support\DatabaseConfig;
use App\Installer\Support\InstallationState;
use App\Installer\Support\StorageConfig;
use App\Models\Properties\UserStatus;
use App\Models\User;
use Illuminate\Support\Env;
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
    public function __construct(private readonly CreateUser $createUser)
    {
    }

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
        // 1. Probe the database before persisting anything irreversible.
        $this->connectDatabase($payload['database']);

        // 2. Persist app URL + database + storage configuration first; a crash
        //    after this still leaves a re-runnable, correctly configured app.
        $this->writeEnv([
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
        $this->writeEnv([
            'APP_INSTALLED' => true,
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'database',
        ]);

        // 7. Drop cached config/routes/views so the new env is honoured.
        $this->clearCaches();

        // 8. Point of no return.
        InstallationState::lock();

        // 9. Cache config/routes/views for production (best effort).
        $this->optimize();

        return $admin;
    }

    /**
     * Upsert the given keys into the app's env file via the framework's
     * line-oriented writer. Booleans are rendered as `true`/`false` so they
     * round-trip through env() instead of collapsing to `1`/empty, and the
     * file is created when absent (Env::writeVariables requires it to exist).
     *
     * @param  array<string, scalar|null>  $values
     */
    private function writeEnv(array $values): void
    {
        $path = app()->environmentFilePath();

        if (! is_file($path)) {
            touch($path);
        }

        Env::writeVariables(
            array_map(
                static fn (mixed $value): mixed => is_bool($value) ? ($value ? 'true' : 'false') : $value,
                $values,
            ),
            $path,
            overwrite: true,
        );
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
            throw InstallationException::atStep(2, __('Could not connect to the database: :error', ['error' => $e->getMessage()]), $e);
        }
    }

    private function migrate(): void
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            throw InstallationException::atStep(2, __('Database migration failed: :error', ['error' => $e->getMessage()]), $e);
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
            throw InstallationException::atStep(4, __('Could not create the administrator account: :error', ['error' => $e->getMessage()]), $e);
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

    private function optimize(): void
    {
        try {
            Artisan::call('optimize');
        } catch (Throwable) {
            // Best effort: caching is a performance optimization that runs after
            // the point of no return, so a failure must not fail a completed install.
        }
    }
}
