<?php

use XBB\Models\User;

test('the settings menu item is visible to administrators', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('admin.settings'));
});

test('the settings menu item is hidden from regular users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('admin.settings'));
});
