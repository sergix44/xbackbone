<?php

use App\Models\User;
use App\Support\Helpers;
use Laravel\Pennant\Feature;

test('a guest falls back to the global default theme', function () {
    Feature::activate('default-theme', 'aqua');

    expect(Helpers::theme(null))->toBe('aqua');
});

test('a user without a personal theme falls back to the global default', function () {
    Feature::activate('default-theme', 'aqua');

    $user = User::factory()->create(['theme' => null]);

    expect(Helpers::theme($user))->toBe('aqua');
});

test('an empty personal theme does not shadow the global default', function () {
    Feature::activate('default-theme', 'aqua');

    // An empty string previously slipped past the `??` fallback and rendered
    // the daisyUI default instead of the configured global theme.
    $user = User::factory()->create(['theme' => '']);

    expect(Helpers::theme($user))->toBe('aqua');
});

test('a personal theme overrides the global default', function () {
    Feature::activate('default-theme', 'aqua');

    $user = User::factory()->create(['theme' => 'dracula']);

    expect(Helpers::theme($user))->toBe('dracula');
});

test('the global default theme renders in the page markup', function () {
    Feature::activate('default-theme', 'aqua');

    $this->actingAs(User::factory()->create(['theme' => '']));

    $this->get(route('user.profile'))->assertSee('data-theme="aqua"', false);
});
