<?php

use App\Actions\Admin\CheckForUpdate;
use App\Livewire\Admin\Updates;
use App\Models\User;
use App\Support\Updater;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

/**
 * Fake the Packagist metadata endpoint with the given list of version strings.
 *
 * @param  list<string>  $versions
 */
function fakePackagist(array $versions): void
{
    Http::fake([
        'repo.packagist.org/*' => Http::response([
            'packages' => [
                'xbackbone/core' => array_map(fn (string $version) => ['version' => $version], $versions),
            ],
        ]),
    ]);
}

/**
 * A partial Updater whose current version is fixed; every other method is real.
 */
function updaterWithCurrent(string $current): Updater
{
    $updater = Mockery::mock(Updater::class)->makePartial();
    $updater->shouldReceive('currentVersion')->andReturn($current);

    return $updater;
}

beforeEach(function () {
    Cache::flush();
});

afterEach(function () {
    putenv('APP_ROOT');
});

test('it detects a newer stable release on packagist', function () {
    fakePackagist(['2.0.0', '2.1.0', '2.0.5']);

    $result = (new CheckForUpdate(updaterWithCurrent('2.0.5')))();

    expect($result['latest'])->toBe('2.1.0')
        ->and($result['updateAvailable'])->toBeTrue();
});

test('it ignores pre-release and development versions when picking the latest', function () {
    fakePackagist(['2.0.0', '2.1.0-beta1', 'dev-master', '2.0.5']);

    $result = (new CheckForUpdate(updaterWithCurrent('2.0.0')))();

    expect($result['latest'])->toBe('2.0.5')
        ->and($result['updateAvailable'])->toBeTrue();
});

test('it normalizes a leading v in tag names', function () {
    fakePackagist(['v2.0.0', 'v2.1.0']);

    $result = (new CheckForUpdate(updaterWithCurrent('2.0.0')))();

    expect($result['latest'])->toBe('2.1.0');
});

test('a development install treats any stable release as an available update', function () {
    fakePackagist(['2.0.0']);

    $result = (new CheckForUpdate(updaterWithCurrent('dev-master')))();

    expect($result['updateAvailable'])->toBeTrue();
});

test('no update is offered when already on the latest version', function () {
    fakePackagist(['2.0.0', '2.0.5']);

    $result = (new CheckForUpdate(updaterWithCurrent('2.0.5')))();

    expect($result['latest'])->toBe('2.0.5')
        ->and($result['updateAvailable'])->toBeFalse();
});

test('an unreachable packagist yields no latest version', function () {
    Http::fake(['repo.packagist.org/*' => Http::response('', 500)]);

    $result = (new CheckForUpdate(updaterWithCurrent('2.0.5')))();

    expect($result['latest'])->toBeNull()
        ->and($result['updateAvailable'])->toBeFalse();
});

test('self-upgrade is supported only in production with a skeleton root', function () {
    expect((new Updater)->isSupported())->toBeFalse();

    app()->detectEnvironment(fn () => 'production');
    putenv('APP_ROOT='.storage_path());

    expect((new Updater)->isSupported())->toBeTrue();

    putenv('APP_ROOT');

    expect((new Updater)->isSupported())->toBeFalse();
});

test('guests are redirected away from the updates tab', function () {
    $this->get(route('admin.settings', ['tab' => 'updates']))->assertRedirect(route('login'));
});

test('non-admin users cannot access the updates tab', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.settings', ['tab' => 'updates']))->assertForbidden();
});

test('admins can open the updates tab', function () {
    fakePackagist(['2.0.0']);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get(route('admin.settings', ['tab' => 'updates']))
        ->assertOk()
        ->assertSee('Current')
        ->assertSee('Latest');
});

test('the updates tab hides the upgrade controls outside production', function () {
    Process::fake();
    fakePackagist(['2.0.0']);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(Updates::class)
        ->assertSet('supported', false)
        ->assertSee('Self-upgrade is only available on production installations.')
        ->assertDontSee('Upgrade to latest')
        ->call('startUpgrade');

    Process::assertNotRan(fn ($process) => str_contains((string) $process->command, 'xbackbone:upgrade'));
});

test('an admin can launch a detached upgrade when one is available', function () {
    Process::fake();
    fakePackagist(['2.1.0']);

    $updater = Mockery::mock(Updater::class)->makePartial();
    $updater->shouldReceive('isSupported')->andReturnTrue();
    $updater->shouldReceive('appRoot')->andReturn(storage_path());
    $updater->shouldReceive('currentVersion')->andReturn('2.0.0');
    $this->app->instance(Updater::class, $updater);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(Updates::class)
        ->assertSet('updateAvailable', true)
        ->assertSee('Upgrade to latest')
        ->call('startUpgrade')
        ->assertSet('state', 'running');

    Process::assertRan(fn ($process) => str_contains((string) $process->command, 'xbackbone:upgrade --to=')
        && str_contains((string) $process->command, '2.1.0'));
});

test('the updates tab surfaces a completed background upgrade while polling', function () {
    fakePackagist(['2.1.0']);

    $updater = Mockery::mock(Updater::class)->makePartial();
    $updater->shouldReceive('isSupported')->andReturnTrue();
    $updater->shouldReceive('appRoot')->andReturn(storage_path());
    $updater->shouldReceive('currentVersion')->andReturn('2.0.0');
    $this->app->instance(Updater::class, $updater);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $updater->markRunning('2.1.0');

    $component = Livewire::test(Updates::class)->assertSet('state', 'running');

    $updater->markDone('2.1.0');

    $component->call('pollStatus')
        ->assertSet('state', 'done')
        ->assertSet('updateAvailable', false);
});
