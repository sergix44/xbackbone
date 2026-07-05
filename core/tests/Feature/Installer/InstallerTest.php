<?php

use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use XBB\Actions\Admin\CreateUser;
use XBB\Installer\Actions\FinalizeInstallation;
use XBB\Installer\Exceptions\InstallationException;
use XBB\Installer\Livewire\Installer;
use XBB\Installer\Support\InstallationState;
use XBB\Models\User;

/**
 * Boot the app as "not yet installed". The route guard checks the state per
 * request, so flipping config here is enough to exercise the installer.
 */
function markNotInstalled(): void
{
    config(['app.installed' => false]);
    InstallationState::unlock();
}

describe('installer gating', function () {
    it('redirects to the installer when the app is not installed', function () {
        markNotInstalled();

        $this->get('/')->assertRedirect(route('installer.index'));
    });

    it('shows the installer when the app is not installed', function () {
        markNotInstalled();

        $this->get(route('installer.index'))
            ->assertOk()
            ->assertSeeLivewire(Installer::class);
    });

    it('redirects away from the installer once installed', function () {
        config(['app.installed' => true]);

        $this->get(route('installer.index'))->assertRedirect(route('login'));
    });

    it('rejects a stale installer snapshot replayed against the Livewire update endpoint after install completes', function () {
        markNotInstalled();

        $html = $this->get(route('installer.index'))->getContent();

        $snapshot = htmlspecialchars_decode(
            str($html)->betweenFirst('wire:snapshot="', '"')->toString(),
            ENT_QUOTES | ENT_SUBSTITUTE,
        );

        config(['app.installed' => true]);

        $replay = fn (string $method) => $this->postJson(EndpointResolver::updatePath(), [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => [
                    'name' => 'Attacker',
                    'email' => 'attacker@evil.test',
                    'password' => 'AttackerPass123!',
                    'password_confirmation' => 'AttackerPass123!',
                    'dbConnectionVerified' => true,
                ],
                'calls' => [
                    ['path' => '', 'method' => $method, 'params' => []],
                ],
            ]],
        ], ['X-Livewire' => 'true']);

        $replay('testDatabase')->assertRedirect(route('login'));
        $replay('install')->assertRedirect(route('login'));

        expect(User::query()->where('email', 'attacker@evil.test')->exists())->toBeFalse();
    });
});

describe('installer step validation', function () {
    it('requires the application url', function () {
        Livewire::test(Installer::class)
            ->set('step', 1)
            ->set('appUrl', '')
            ->call('nextStep')
            ->assertHasErrors(['appUrl' => 'required']);
    });

    it('requires a sqlite path when using the sqlite driver', function () {
        Livewire::test(Installer::class)
            ->set('step', 2)
            ->set('dbDriver', 'sqlite')
            ->set('dbSqlitePath', '')
            ->call('nextStep')
            ->assertHasErrors(['dbSqlitePath' => 'required']);
    });

    it('requires connection fields for server drivers', function () {
        Livewire::test(Installer::class)
            ->set('step', 2)
            ->set('dbDriver', 'mysql')
            ->set('dbDatabase', '')
            ->set('dbUsername', '')
            ->call('nextStep')
            ->assertHasErrors(['dbDatabase', 'dbUsername']);
    });

    it('cannot advance past the database step until the connection is verified', function () {
        Livewire::test(Installer::class)
            ->set('step', 2)
            ->set('dbDriver', 'sqlite')
            ->set('dbSqlitePath', '/tmp/whatever.sqlite')
            ->set('dbConnectionVerified', false)
            ->call('nextStep')
            ->assertSet('step', 2);
    });

    it('requires a root path for the local storage driver', function () {
        Livewire::test(Installer::class)
            ->set('step', 3)
            ->set('storageDriver', 'local')
            ->set('localRoot', '')
            ->call('nextStep')
            ->assertHasErrors(['localRoot' => 'required']);
    });

    it('requires credentials for the s3 storage driver', function () {
        Livewire::test(Installer::class)
            ->set('step', 3)
            ->set('storageDriver', 's3')
            ->call('nextStep')
            ->assertHasErrors(['s3Key', 's3Secret', 's3Region', 's3Bucket']);
    });

    it('validates the administrator account', function () {
        Livewire::test(Installer::class)
            ->set('step', 4)
            ->set('name', '')
            ->set('email', 'not-an-email')
            ->set('password', 'short')
            ->set('password_confirmation', 'different')
            ->call('nextStep')
            ->assertHasErrors(['name', 'email', 'password']);
    });

    it('requires matching password confirmation', function () {
        Livewire::test(Installer::class)
            ->set('step', 4)
            ->set('name', 'Admin')
            ->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'mismatch123')
            ->call('nextStep')
            ->assertHasErrors(['password']);
    });

    it('skips legacy validation when import is disabled', function () {
        Livewire::test(Installer::class)
            ->set('step', 5)
            ->set('importLegacy', false)
            ->call('nextStep')
            ->assertHasNoErrors();
    });

    it('requires legacy fields when import is enabled', function () {
        Livewire::test(Installer::class)
            ->set('step', 5)
            ->set('importLegacy', true)
            ->set('legacyDriver', 'mysql')
            ->call('nextStep')
            ->assertHasErrors(['legacyStoragePath', 'legacyDbDatabase', 'legacyDbUsername']);
    });
});

describe('installer connection probes', function () {
    it('verifies a writable sqlite connection', function () {
        $path = sys_get_temp_dir().'/xbb-probe-'.uniqid().'.sqlite';

        Livewire::test(Installer::class)
            ->set('step', 2)
            ->set('dbDriver', 'sqlite')
            ->set('dbSqlitePath', $path)
            ->call('testDatabase')
            ->assertSet('dbConnectionVerified', true);

        @unlink($path);
    });

    it('reports a failure for an unwritable sqlite directory', function () {
        Livewire::test(Installer::class)
            ->set('step', 2)
            ->set('dbDriver', 'sqlite')
            ->set('dbSqlitePath', '/this/path/does/not/exist/xbb.db')
            ->call('testDatabase')
            ->assertSet('dbConnectionVerified', false);
    });
});

describe('installer finalize wiring', function () {
    it('finalizes and redirects to the login page', function () {
        $spy = new class(app(CreateUser::class)) extends FinalizeInstallation
        {
            /** @var array<string, mixed> */
            public array $received = [];

            public function __invoke(array $payload): User
            {
                $this->received = $payload;

                return User::factory()->create();
            }
        };

        app()->instance(FinalizeInstallation::class, $spy);

        Livewire::test(Installer::class)
            ->set('appUrl', 'https://files.example.com')
            ->set('dbDriver', 'sqlite')
            ->set('dbSqlitePath', '/tmp/xbb.db')
            ->set('dbConnectionVerified', true)
            ->set('storageDriver', 'local')
            ->set('localRoot', storage_path('app'))
            ->set('name', 'Admin')
            ->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('importLegacy', false)
            ->call('install')
            ->assertRedirect(route('login'));

        expect($spy->received['appUrl'])->toBe('https://files.example.com')
            ->and($spy->received['database']['driver'])->toBe('sqlite')
            ->and($spy->received['admin']['email'])->toBe('admin@example.com')
            ->and($spy->received['import'])->toBeNull();
    });

    it('jumps back to the offending step when finalize fails', function () {
        $spy = new class(app(CreateUser::class)) extends FinalizeInstallation
        {
            public function __invoke(array $payload): User
            {
                throw InstallationException::atStep(2, 'connection refused');
            }
        };

        app()->instance(FinalizeInstallation::class, $spy);

        Livewire::test(Installer::class)
            ->set('appUrl', 'https://files.example.com')
            ->set('dbDriver', 'sqlite')
            ->set('dbSqlitePath', '/tmp/xbb.db')
            ->set('dbConnectionVerified', true)
            ->set('storageDriver', 'local')
            ->set('localRoot', storage_path('app'))
            ->set('name', 'Admin')
            ->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('importLegacy', false)
            ->call('install')
            ->assertNoRedirect()
            ->assertSet('step', 2);
    });

    it('blocks installation until the database connection is verified', function () {
        Livewire::test(Installer::class)
            ->set('appUrl', 'https://files.example.com')
            ->set('dbDriver', 'sqlite')
            ->set('dbSqlitePath', '/tmp/xbb.db')
            ->set('dbConnectionVerified', false)
            ->set('storageDriver', 'local')
            ->set('localRoot', storage_path('app'))
            ->set('name', 'Admin')
            ->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('importLegacy', false)
            ->call('install')
            ->assertNoRedirect()
            ->assertSet('step', 2);
    });
});
