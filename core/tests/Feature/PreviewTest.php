<?php

use App\Models\Resource;

test('preview page displays an image resource with its metadata', function () {
    $resource = Resource::factory()->image()->create();

    $this->get(route('preview.ext', ['resource' => $resource->code, 'ext' => $resource->extension]))
        ->assertOk()
        ->assertSee($resource->filename)
        ->assertSee($resource->raw_url, false)
        ->assertSee($resource->size_human_readable)
        ->assertSee($resource->mime)
        ->assertSee($resource->user->name)
        ->assertSee('Dimensions');
});

test('preview page embeds a pdf viewer for pdf resources', function () {
    $resource = Resource::factory()->pdf()->create();

    $this->get(route('preview.ext', ['resource' => $resource->code, 'ext' => $resource->extension]))
        ->assertOk()
        ->assertSee('<object', false)
        ->assertSee('type="'.$resource->mime.'"', false)
        ->assertSee($resource->raw_url, false)
        ->assertDontSee('No preview available');
});

test('preview page shows a placeholder for non displayable resources', function () {
    $resource = Resource::factory()->create();

    $this->get(route('preview', ['resource' => $resource->code]))
        ->assertOk()
        ->assertSee($resource->filename)
        ->assertSee('No preview available');
});

test('preview page shows publish and expiry dates when present', function () {
    $resource = Resource::factory()->image()->create([
        'published_at' => now()->subDay(),
        'expires_at' => now()->addWeek(),
    ]);

    $this->get(route('preview', ['resource' => $resource->code]))
        ->assertOk()
        ->assertSee('Published')
        ->assertSee('Expires');
});

test('preview page hides publish and expiry dates when absent', function () {
    $resource = Resource::factory()->image()->create();

    $this->get(route('preview', ['resource' => $resource->code]))
        ->assertOk()
        ->assertDontSee('Published')
        ->assertDontSee('Expires');
});

test('preview page wires the copy link button to the resource url', function () {
    $resource = Resource::factory()->image()->create();

    $this->get(route('preview.ext', ['resource' => $resource->code, 'ext' => $resource->extension]))
        ->assertOk()
        ->assertSee("\$clipboard('{$resource->preview_ext_url}')", false);
});

test('preview page returns 404 when the extension does not match', function () {
    $resource = Resource::factory()->image()->create();

    $this->get(route('preview.ext', ['resource' => $resource->code, 'ext' => 'zip']))
        ->assertNotFound();
});
