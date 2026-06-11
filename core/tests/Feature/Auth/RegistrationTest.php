<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Register;
use Livewire\Livewire;

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
