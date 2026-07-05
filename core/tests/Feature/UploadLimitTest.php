<?php

use Livewire\Livewire;
use XBB\Livewire\Dashboard;
use XBB\Models\Resource;
use XBB\Models\User;
use XBB\Support\Helpers;

test('the upload drawer shows the maximum upload size', function () {
    $this->actingAs(User::factory()->create());

    $expected = Helpers::humanizeBytes(min(array_filter([
        Helpers::iniSizeToBytes((string) ini_get('upload_max_filesize')),
        Helpers::iniSizeToBytes((string) ini_get('post_max_size')),
    ])));

    Livewire::test(Dashboard::class)
        ->assertSee('Maximum upload size:')
        ->assertSee($expected);
});

test('the upload drawer shows the storage quota usage for a limited user', function () {
    $user = User::factory()->create();
    $user->quota = 100 * 1024 * 1024; // 100 MB
    $user->save();

    Resource::factory()->for($user)->create(['size' => 25 * 1024 * 1024]);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSee(__('Storage used'))
        ->assertSee('25.00 MB')
        ->assertSee('100.00 MB');
});

test('the upload drawer shows an unlimited storage quota', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Dashboard::class)
        ->assertSee(__('Storage used'))
        ->assertSee(__('Unlimited'));
});
