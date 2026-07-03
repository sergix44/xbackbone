<?php

use XBB\Models\Resource;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

test('serves the generated preview file', function () {
    $resource = Resource::factory()->image()->withPreview()->create();
    Storage::put($resource->preview_path, 'fake-webp-bytes');

    $this->get(route('thumbnail', $resource->code))
        ->assertOk();
});

test('falls back to the raw file for displayable images without a preview', function () {
    $resource = Resource::factory()->image()->create();
    Storage::put($resource->storage_path, 'fake-png-bytes');

    $this->get(route('thumbnail', $resource->code))
        ->assertOk();
});

test('returns 404 when there is no preview and the resource is not displayable', function () {
    $resource = Resource::factory()->create();

    $this->get(route('thumbnail', $resource->code))
        ->assertNotFound();
});
