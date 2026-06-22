<?php

use App\Actions\Resource\ToggleResourceVisibility;
use App\Livewire\Dashboard;
use App\Models\Resource;
use App\Models\User;
use Livewire\Livewire;

test('the action toggles the private flag', function () {
    $resource = Resource::factory()->create(['is_private' => false]);

    app(ToggleResourceVisibility::class)($resource);
    expect($resource->fresh()->is_private)->toBeTrue();

    app(ToggleResourceVisibility::class)($resource);
    expect($resource->fresh()->is_private)->toBeFalse();
});

test('the dashboard toggles visibility of an owned resource', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->for($user)->create(['is_private' => false]);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->call('toggleVisibility', $resource->id)
        ->assertHasNoErrors();

    expect($resource->fresh()->is_private)->toBeTrue();
});

test('the dashboard refuses to toggle another users resource', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $resource = Resource::factory()->for($owner)->create(['is_private' => false]);

    $this->actingAs($other);

    Livewire::test(Dashboard::class)
        ->call('toggleVisibility', $resource->id);

    expect($resource->fresh()->is_private)->toBeFalse();
});
