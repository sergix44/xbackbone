<?php

use App\Livewire\User\Profile;
use App\Models\Properties\ResourceType;
use App\Models\Resource;
use App\Models\User;
use Livewire\Livewire;

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
    ]);
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
