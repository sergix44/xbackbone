<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use XBB\Livewire\User\Profile;
use XBB\Models\User;

test('password can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('currentPassword', 'password')
        ->set('newPassword', 'new-password')
        ->call('updateProfile')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'user.password_changed',
        'subject_id' => $user->id,
        'causer_id' => $user->id,
    ]);
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('currentPassword', 'wrong-password')
        ->set('newPassword', 'new-password')
        ->call('updateProfile')
        ->assertHasErrors(['current_password']);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});
