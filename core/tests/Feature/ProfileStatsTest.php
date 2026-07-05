<?php

use Livewire\Livewire;
use XBB\Livewire\User\Profile;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;
use XBB\Models\User;

test('the profile header aggregates real statistics for the authenticated user', function () {
    $user = User::factory()->create();

    Resource::factory()->for($user)->create(['size' => 3 * 1024 * 1024, 'views' => 40, 'downloads' => 5]);
    Resource::factory()->for($user)->create(['size' => 1 * 1024 * 1024, 'views' => 2, 'downloads' => 2]);

    // Directories are containers, not uploaded media, so they must not be counted.
    Resource::factory()->for($user)->create(['type' => ResourceType::DIRECTORY, 'size' => null]);

    // Another user's resources must never leak into these stats.
    Resource::factory()->create(['size' => 9 * 1024 * 1024, 'views' => 99, 'downloads' => 99]);

    $this->actingAs($user);

    expect(Livewire::test(Profile::class)->instance()->stats())->toBe([
        'media' => '2',
        'size' => '4.00 MB',
        'views' => '42',
        'downloads' => '7',
        'quota' => null,
        'quota_unlimited' => true,
        'quota_percent' => null,
    ]);
});

test('the profile stats expose the quota usage for a limited user', function () {
    $user = User::factory()->create();
    $user->quota = 100 * 1024 * 1024; // 100 MB
    $user->save();

    Resource::factory()->for($user)->create(['size' => 4 * 1024 * 1024]);

    $this->actingAs($user);

    $stats = Livewire::test(Profile::class)->instance()->stats();

    expect($stats['quota'])->toBe('100.00 MB')
        ->and($stats['quota_unlimited'])->toBeFalse()
        ->and($stats['quota_percent'])->toBe(4);
});

test('the profile shows a quota bar for a limited user', function () {
    $user = User::factory()->create();
    $user->quota = 100 * 1024 * 1024;
    $user->save();

    Resource::factory()->for($user)->create(['size' => 4 * 1024 * 1024]);

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->assertSee(__('Storage quota'))
        ->assertSee('100.00 MB');
});

test('the profile hides the quota bar for an unlimited user', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Profile::class)
        ->assertDontSee(__('Storage quota'));
});

test('the profile header renders the statistics instead of placeholders', function () {
    $user = User::factory()->create();
    Resource::factory()->for($user)->create(['size' => 4 * 1024 * 1024, 'views' => 42, 'downloads' => 7]);

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->assertSee('4.00 MB')
        ->assertSee(__('media'))
        ->assertSee(__('views'))
        ->assertSee(__('downloads'))
        ->assertDontSee('posts')
        ->assertDontSee('comments');
});
