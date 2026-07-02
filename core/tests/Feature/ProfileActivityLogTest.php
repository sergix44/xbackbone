<?php

use App\Livewire\User\Profile;
use App\Models\User;
use Livewire\Livewire;

test('updating the profile logs user.profile_updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('name', 'Renamed')
        ->set('email', $user->email)
        ->call('updateProfile')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'user.profile_updated',
        'subject_id' => $user->id,
        'causer_id' => $user->id,
    ]);
});

test('changing the password alongside the profile also logs user.password_changed', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('currentPassword', 'password')
        ->set('newPassword', 'NewPassword123!')
        ->call('updateProfile')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'user.profile_updated',
        'subject_id' => $user->id,
        'causer_id' => $user->id,
    ]);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'user.password_changed',
        'subject_id' => $user->id,
        'causer_id' => $user->id,
    ]);
});

test('revoking a token logs token.revoked', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token');

    $this->actingAs($user);

    Livewire::test(Profile::class, ['tab' => 'tokens'])
        ->set('selectedTokens', [$token->accessToken->id])
        ->call('revokeSelectedTokens')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'token.revoked',
        'subject_id' => $token->accessToken->id,
        'causer_id' => $user->id,
    ]);
});
