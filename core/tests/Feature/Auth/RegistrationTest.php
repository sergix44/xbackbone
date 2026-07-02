<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Register;
use App\Models\User;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

beforeEach(function () {
    Feature::activate('signup');
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeLivewire(Register::class);
});

test('new users can register', function () {
    $component = Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'auth.registered',
        'causer_id' => User::where('email', 'test@example.com')->value('id'),
    ]);
});

test('new users can not register with mismatched password confirmation', function () {
    $component = Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'wrong-password');

    $component->call('register');

    $component
        ->assertHasErrors(['password'])
        ->assertNoRedirect();

    $this->assertGuest();
});

test('the registration screen returns 404 when sign up is disabled', function () {
    Feature::deactivate('signup');

    $this->get('/register')->assertNotFound();
});

test('registering returns 404 and creates no user when sign up is disabled', function () {
    Feature::deactivate('signup');

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
});
