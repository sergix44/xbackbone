<?php

use App\Livewire\Admin\Settings;
use App\Models\Properties\ResourceType;
use App\Models\Resource;
use App\Models\User;
use Livewire\Livewire;

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
        ->assertSee('General settings will be available here.');
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
