<?php

use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use XBB\Actions\Resource\UpdateResourceSettings;
use XBB\Livewire\Dashboard;
use XBB\Models\Resource;
use XBB\Models\User;

test('the action sets and clears the expiration', function () {
    $resource = Resource::factory()->create();

    app(UpdateResourceSettings::class)($resource, ['expires_at' => '2099-01-01T10:00']);
    expect($resource->fresh()->expires_at)->not->toBeNull();

    app(UpdateResourceSettings::class)($resource, ['expires_at' => null]);
    expect($resource->fresh()->expires_at)->toBeNull();
});

test('the action sets, keeps and clears the password', function () {
    $resource = Resource::factory()->create();

    app(UpdateResourceSettings::class)($resource, ['password' => 'topsecret']);
    expect(Hash::check('topsecret', $resource->fresh()->password))->toBeTrue();

    // A blank password leaves the current one untouched.
    app(UpdateResourceSettings::class)($resource, ['password' => null]);
    expect(Hash::check('topsecret', $resource->fresh()->password))->toBeTrue();

    // Explicit removal clears it.
    app(UpdateResourceSettings::class)($resource, ['remove_password' => true]);
    expect($resource->fresh()->password)->toBeNull();
});

test('editSettings populates the form without exposing the password hash', function () {
    $owner = User::factory()->create();
    $resource = Resource::factory()->for($owner)->passwordProtected()->create(['expires_at' => now()->addWeek()]);

    $this->actingAs($owner);

    Livewire::test(Dashboard::class)
        ->call('editSettings', $resource->id)
        ->assertSet('showSettingsModal', true)
        ->assertSet('settingsHasPassword', true)
        ->assertSet('settingsPassword', null)
        ->assertSet('settingsId', $resource->id);
});

test('saveSettings updates an owned resource and closes the modal', function () {
    $owner = User::factory()->create();
    $resource = Resource::factory()->for($owner)->create();

    $this->actingAs($owner);

    Livewire::test(Dashboard::class)
        ->call('editSettings', $resource->id)
        ->set('settingsExpiresAt', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('settingsPassword', 'secret')
        ->call('saveSettings')
        ->assertHasNoErrors()
        ->assertSet('showSettingsModal', false);

    $resource->refresh();

    expect($resource->expires_at)->not->toBeNull()
        ->and(Hash::check('secret', $resource->password))->toBeTrue();
});

test('saveSettings rejects a past expiration', function () {
    $owner = User::factory()->create();
    $resource = Resource::factory()->for($owner)->create();

    $this->actingAs($owner);

    Livewire::test(Dashboard::class)
        ->call('editSettings', $resource->id)
        ->set('settingsExpiresAt', now()->subDay()->format('Y-m-d\TH:i'))
        ->call('saveSettings')
        ->assertHasErrors('settingsExpiresAt');
});

test('saveSettings rejects a too short password', function () {
    $owner = User::factory()->create();
    $resource = Resource::factory()->for($owner)->create();

    $this->actingAs($owner);

    Livewire::test(Dashboard::class)
        ->call('editSettings', $resource->id)
        ->set('settingsPassword', 'ab')
        ->call('saveSettings')
        ->assertHasErrors('settingsPassword');
});

test('a user cannot change settings of another users resource', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $resource = Resource::factory()->for($owner)->create();

    $this->actingAs($other);

    Livewire::test(Dashboard::class)
        ->set('settingsId', $resource->id)
        ->set('settingsPassword', 'secret')
        ->call('saveSettings');

    expect($resource->fresh()->password)->toBeNull();
});
