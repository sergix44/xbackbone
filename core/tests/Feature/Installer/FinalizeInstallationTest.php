<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use XBB\Installer\Actions\FinalizeInstallation;
use XBB\Installer\Support\InstallationState;
use XBB\Models\User;

beforeEach(function () {
    $this->originalConnection = config('database.default');

    $this->tempDir = sys_get_temp_dir().'/xbb-finalize-'.uniqid();
    mkdir($this->tempDir);
    mkdir($this->tempDir.'/storage', 0775, true);
    mkdir($this->tempDir.'/uploads', 0775, true);

    // Redirect env + storage writes to the temp dir so the real .env and
    // storage marker are never touched.
    $this->app->useEnvironmentPath($this->tempDir);
    $this->app->useStoragePath($this->tempDir.'/storage');

    // Stub the final optimize step: the real command runs config:cache, which
    // reloads the running config (dropping the runtime "install" connection) and
    // writes a cache file into the real bootstrap/cache. It is a best-effort
    // performance step, irrelevant to what these tests assert.
    Artisan::command('optimize', fn () => 0);
});

afterEach(function () {
    // FinalizeInstallation switches the default connection to "install"; restore
    // it before RefreshDatabase tears down so its rollback targets the right
    // connection and the in-memory transaction does not leak into later tests.
    config(['database.default' => $this->originalConnection]);
    DB::purge('install');

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->tempDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
    }

    @rmdir($this->tempDir);
});

it('performs a full install with sqlite and local storage', function () {
    $payload = [
        'appUrl' => 'https://files.example.com',
        'database' => [
            'driver' => 'sqlite',
            'sqlitePath' => $this->tempDir.'/xbb.db',
        ],
        'storage' => [
            'driver' => 'local',
            'root' => $this->tempDir.'/uploads',
        ],
        'admin' => [
            'name' => 'First Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ],
        'queue' => ['sync' => false],
        'import' => null,
    ];

    $admin = app(FinalizeInstallation::class)($payload);

    // The administrator is created, verified and flagged as admin.
    expect($admin->is_admin)->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and(User::query()->where('email', 'admin@example.com')->where('is_admin', true)->exists())->toBeTrue()
        ->and(User::query()->count())->toBe(1);

    // The installation marker is written, deactivating the installer.
    expect(InstallationState::isInstalled())->toBeTrue()
        ->and(is_file($this->tempDir.'/storage/installed'))->toBeTrue();

    // The chosen configuration is persisted to the env file.
    $env = file_get_contents($this->tempDir.'/.env');

    // Env::writeVariables() quotes any value that is not purely alphanumeric,
    // so the URL is written double-quoted (phpdotenv reads it back unquoted).
    expect($env)
        ->toContain('APP_INSTALLED=true')
        ->toContain('APP_URL="https://files.example.com"')
        ->toContain('DB_CONNECTION=sqlite')
        ->toContain('SESSION_DRIVER=database')
        ->toContain('QUEUE_CONNECTION=database');
});

it('writes the synchronous queue connection when the operator opts out of a worker', function () {
    $payload = [
        'appUrl' => 'https://files.example.com',
        'database' => ['driver' => 'sqlite', 'sqlitePath' => $this->tempDir.'/xbb.db'],
        'storage' => ['driver' => 'local', 'root' => $this->tempDir.'/uploads'],
        'admin' => ['name' => 'First Admin', 'email' => 'admin@example.com', 'password' => 'password123'],
        'queue' => ['sync' => true],
        'import' => null,
    ];

    app(FinalizeInstallation::class)($payload);

    expect(file_get_contents($this->tempDir.'/.env'))->toContain('QUEUE_CONNECTION=sync');
});

it('is idempotent when re-run with the same administrator email', function () {
    $payload = [
        'appUrl' => 'https://files.example.com',
        'database' => ['driver' => 'sqlite', 'sqlitePath' => $this->tempDir.'/xbb.db'],
        'storage' => ['driver' => 'local', 'root' => $this->tempDir.'/uploads'],
        'admin' => ['name' => 'First Admin', 'email' => 'admin@example.com', 'password' => 'password123'],
        'queue' => ['sync' => false],
        'import' => null,
    ];

    app(FinalizeInstallation::class)($payload);
    app(FinalizeInstallation::class)($payload);

    expect(User::query()->where('email', 'admin@example.com')->count())->toBe(1);
});
