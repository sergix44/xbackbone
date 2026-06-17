<?php

use App\Livewire\User\Profile;
use App\Models\User;
use Livewire\Livewire;

test('an invalid email surfaces a validation error', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('email', 'not-an-email')
        ->call('updateProfile')
        ->assertHasErrors('email');
});

test('a wrong current password surfaces an error keyed to the password field', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('currentPassword', 'wrong-password')
        ->set('newPassword', 'NewPassword123!')
        ->call('updateProfile')
        ->assertHasErrors('current_password');
});

test('a successful update clears the password fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('name', 'Renamed')
        ->set('email', $user->email)
        ->set('currentPassword', 'password')
        ->set('newPassword', 'NewPassword123!')
        ->call('updateProfile')
        ->assertHasNoErrors()
        ->assertSet('currentPassword', null)
        ->assertSet('newPassword', null);

    expect($user->fresh()->name)->toBe('Renamed');
});
