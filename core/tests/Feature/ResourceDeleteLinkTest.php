<?php

use XBB\Models\Resource;
use Illuminate\Support\Facades\Storage;

test('a signed deletion url deletes the resource', function () {
    $resource = Resource::factory()->link()->create();

    $this->get($resource->deletion_url)
        ->assertRedirect(route('dashboard'));

    expect(Resource::find($resource->id))->toBeNull();
});

test('a deletion url removes the stored file', function () {
    Storage::fake();

    $resource = Resource::factory()->create(['fingerprint' => 'fixed-fingerprint']);
    Storage::put($resource->storage_path, 'contents');

    $this->get($resource->deletion_url)->assertRedirect(route('dashboard'));

    expect(Resource::find($resource->id))->toBeNull();
    Storage::assertMissing($resource->storage_path);
});

test('an unsigned deletion url is rejected', function () {
    $resource = Resource::factory()->link()->create();

    $this->get(route('resource.delete', ['resource' => $resource->code]))
        ->assertForbidden();

    expect(Resource::find($resource->id))->not->toBeNull();
});

test('a tampered deletion url is rejected', function () {
    $resource = Resource::factory()->link()->create();

    $this->get($resource->deletion_url.'tampered')
        ->assertForbidden();

    expect(Resource::find($resource->id))->not->toBeNull();
});
