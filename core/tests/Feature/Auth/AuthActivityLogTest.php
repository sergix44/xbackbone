<?php

use XBB\Livewire\Auth\Login;
use XBB\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

test('a successful login logs auth.login', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('form.email', $user->email)
        ->set('form.password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'auth.login',
        'causer_id' => $user->id,
    ]);
});

test('logging out logs auth.logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'));

    $this->assertDatabaseHas('activity_log', [
        'description' => 'auth.logout',
        'causer_id' => $user->id,
    ]);
});

test('a failed login attempt logs auth.failed without leaking the password', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password')
        ->call('authenticate');

    $activity = Activity::query()->where('description', 'auth.failed')->firstOrFail();

    expect($activity->causer_id)->toBe($user->id);
    expect($activity->properties->get('email'))->toBe($user->email);
    expect($activity->properties->has('password'))->toBeFalse();
});

test('repeated failed logins trigger auth.lockout', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password')
            ->call('authenticate');
    }

    Livewire::test(Login::class)
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password')
        ->call('authenticate');

    $this->assertDatabaseHas('activity_log', [
        'description' => 'auth.lockout',
    ]);
});
