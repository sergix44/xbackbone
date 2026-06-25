<?php

use App\Installer\Actions\FinalizeInstallation;
use App\Installer\Support\InstallationState;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

    expect($env)
        ->toContain('APP_INSTALLED=true')
        ->toContain('APP_URL=https://files.example.com')
        ->toContain('DB_CONNECTION=sqlite')
        ->toContain('SESSION_DRIVER=database');
});

it('is idempotent when re-run with the same administrator email', function () {
    $payload = [
        'appUrl' => 'https://files.example.com',
        'database' => ['driver' => 'sqlite', 'sqlitePath' => $this->tempDir.'/xbb.db'],
        'storage' => ['driver' => 'local', 'root' => $this->tempDir.'/uploads'],
        'admin' => ['name' => 'First Admin', 'email' => 'admin@example.com', 'password' => 'password123'],
        'import' => null,
    ];

    app(FinalizeInstallation::class)($payload);
    app(FinalizeInstallation::class)($payload);

    expect(User::query()->where('email', 'admin@example.com')->count())->toBe(1);
});
