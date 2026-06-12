<?php

use App\Livewire\Dashboard;
use App\Models\User;
use App\Support\Helpers;
use Livewire\Livewire;

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
