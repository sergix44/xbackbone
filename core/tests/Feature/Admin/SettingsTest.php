<?php

use Laravel\Pennant\Feature;
use Livewire\Livewire;
use XBB\Livewire\Admin\Settings;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;
use XBB\Models\User;

test('guests are redirected away from the settings page', function () {
    $this->get(route('admin.settings'))->assertRedirect(route('login'));
});

test('non-admin users cannot access the settings page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.settings'))->assertForbidden();
});

test('admins can access the settings page and see the navigation menu', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('General Settings')
        ->assertSee('User Management')
        ->assertSee('Statistics');
});

test('the settings page defaults to the general tab', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(Settings::class)
        ->assertSet('tab', 'general')
        ->assertSee('Enable user sign up')
        ->assertSee('Default theme')
        ->assertSee('Make API documentation public');
});

test('the general tab warns when the queue runs synchronously', function () {
    config(['queue.default' => 'sync']);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(Settings::class)->assertSee('Queue running synchronously');
});

test('the general tab hides the synchronous queue warning when a real queue is configured', function () {
    config(['queue.default' => 'database']);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(Settings::class)->assertDontSee('Queue running synchronously');
});

test('the general tab reflects the current feature values', function () {
    Feature::activate('signup');
    Feature::activate('default-theme', 'aqua');
    Feature::activate('public-api-docs');

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(Settings::class)
        ->assertSet('signupEnabled', true)
        ->assertSet('defaultTheme', 'aqua')
        ->assertSet('apiDocsPublic', true);
});

test('an admin can enable and disable user sign up', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(Settings::class)
        ->set('signupEnabled', true)
        ->call('updateSignup');

    expect(Feature::value('signup'))->toBeTrue();

    Livewire::test(Settings::class)
        ->set('signupEnabled', false)
        ->call('updateSignup');

    expect(Feature::value('signup'))->toBeFalse();
});

test('an admin can make the api documentation public and restrict it again', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(Settings::class)
        ->set('apiDocsPublic', true)
        ->call('updateApiDocsPublic');

    expect(Feature::value('public-api-docs'))->toBeTrue();

    Livewire::test(Settings::class)
        ->set('apiDocsPublic', false)
        ->call('updateApiDocsPublic');

    expect(Feature::value('public-api-docs'))->toBeFalse();
});

test('an admin can set the global default theme', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(Settings::class)
        ->set('defaultTheme', 'dracula')
        ->call('updateDefaultTheme');

    expect(Feature::value('default-theme'))->toBe('dracula');
});

test('the settings page resolves the requested tab', function (string $tab, string $content) {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(Settings::class, ['tab' => $tab])
        ->assertSet('tab', $tab)
        ->assertSee($content);
})->with([
    'users' => ['users', 'New user'],
    'statistics' => ['statistics', 'Media by Type'],
]);

test('an unknown tab returns a not found response', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get(route('admin.settings', ['tab' => 'unknown']))->assertNotFound();
});

test('the statistics tab aggregates system-wide totals across every user', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $other = User::factory()->create();

    Resource::factory()->for($admin)->create(['size' => 3 * 1024 * 1024, 'views' => 40, 'downloads' => 5]);
    Resource::factory()->for($other)->create(['size' => 1 * 1024 * 1024, 'views' => 2, 'downloads' => 2]);

    // Directories are containers, not uploaded media, so they must never be counted.
    Resource::factory()->for($admin)->create(['type' => ResourceType::DIRECTORY, 'size' => null]);

    $this->actingAs($admin);

    expect(Livewire::test(Settings::class, ['tab' => 'statistics'])->instance()->stats())->toBe([
        'users' => '2',
        'media' => '2',
        'size' => '4.00 MB',
        'views' => '42',
        'downloads' => '7',
    ]);
});

test('the statistics tab breaks media down by type, ordered by count', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Resource::factory()->for($admin)->image()->count(2)->create();
    Resource::factory()->for($admin)->video()->create();
    Resource::factory()->for($admin)->create(['type' => ResourceType::DIRECTORY, 'size' => null]);

    $this->actingAs($admin);

    $breakdown = Livewire::test(Settings::class, ['tab' => 'statistics'])->instance()->typeBreakdown();

    expect($breakdown)->toHaveCount(2);
    expect($breakdown[0])->toMatchArray(['label' => 'Image', 'count' => '2']);
    expect($breakdown[1])->toMatchArray(['label' => 'Video', 'count' => '1']);
});

test('the statistics tab ranks the top uploaders by media count, excluding directories and users without media', function () {
    $top = User::factory()->create(['is_admin' => true, 'name' => 'Top Admin']);
    $light = User::factory()->create(['name' => 'Light User']);
    $foldersOnly = User::factory()->create(['name' => 'Folders Only']);
    User::factory()->create(['name' => 'No Uploads']);

    Resource::factory()->for($top)->count(3)->create(['size' => 1024 * 1024]);
    // A directory must never inflate its owner's media count or storage.
    Resource::factory()->for($top)->create(['type' => ResourceType::DIRECTORY, 'size' => null]);
    Resource::factory()->for($light)->create(['size' => 512 * 1024]);
    // A user whose only resource is a directory has uploaded no media and must be excluded.
    Resource::factory()->for($foldersOnly)->create(['type' => ResourceType::DIRECTORY, 'size' => null]);

    $this->actingAs($top);

    $uploaders = Livewire::test(Settings::class, ['tab' => 'statistics'])->instance()->topUploaders();

    expect($uploaders)->toHaveCount(2);
    expect($uploaders[0])->toMatchArray(['name' => 'Top Admin', 'media' => '3', 'size' => '3.00 MB']);
    expect($uploaders[1]['name'])->toBe('Light User');
    expect(collect($uploaders)->pluck('name'))->not->toContain('Folders Only');
});

test('the statistics tab renders the aggregated values and section headings', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Resource::factory()->for($admin)->image()->create(['size' => 4 * 1024 * 1024, 'views' => 42, 'downloads' => 7]);

    $this->actingAs($admin);

    Livewire::test(Settings::class, ['tab' => 'statistics'])
        ->assertSee('Media by Type')
        ->assertSee('Top Uploaders')
        ->assertSee('4.00 MB')
        ->assertSee('Image');
});

test('the statistics tab shows an empty state when no media exist', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(Settings::class, ['tab' => 'statistics'])
        ->assertSee('No media uploaded yet.');
});
