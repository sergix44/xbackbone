<?php

namespace Tests\Feature\Auth;

use App\Livewire\User\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('password can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('currentPassword', 'password')
        ->set('newPassword', 'new-password')
        ->call('updateProfile')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
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
