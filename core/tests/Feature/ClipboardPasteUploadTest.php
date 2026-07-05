<?php

use Livewire\Livewire;
use XBB\Livewire\Dashboard;
use XBB\Models\User;

test('the upload drawer wires a clipboard paste handler that opens the drawer and uploads', function () {
    $this->actingAs(User::factory()->create());

    // The paste handler is the only one that calls uploadFiles() with the bare
    // clipboard FileList (drop uses e.dataTransfer.files, change uses e.target.files)
    // and opens the drawer before uploading.
    Livewire::test(Dashboard::class)
        ->assertSeeHtml('$wire.showUploadDrawer = true')
        ->assertSeeHtml('this.uploadFiles(files)');
});
