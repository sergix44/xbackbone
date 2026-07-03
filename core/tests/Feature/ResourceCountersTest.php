<?php

use XBB\Models\Resource;
use Illuminate\Support\Facades\Storage;

test('visiting the preview page records a view', function () {
    $resource = Resource::factory()->image()->create();

    $this->get(route('preview', ['resource' => $resource->code]))->assertOk();

    expect($resource->fresh()->views)->toBe(1)
        ->and($resource->fresh()->downloads)->toBe(0);
});

test('each preview page visit records another view', function () {
    $resource = Resource::factory()->image()->create();

    $this->get(route('preview', ['resource' => $resource->code]))->assertOk();
    $this->get(route('preview', ['resource' => $resource->code]))->assertOk();

    expect($resource->fresh()->views)->toBe(2);
});

test('visiting a link preview records a view but no download', function () {
    $resource = Resource::factory()->link()->create(['data' => 'https://example.com/destination']);

    $this->get(route('preview', ['resource' => $resource->code]))->assertOk();

    expect($resource->fresh()->views)->toBe(1)
        ->and($resource->fresh()->downloads)->toBe(0);
});

test('an inaccessible resource does not record a view', function () {
    $resource = Resource::factory()->image()->create(['is_private' => true]);

    $this->get(route('preview', ['resource' => $resource->code]))->assertNotFound();

    expect($resource->fresh()->views)->toBe(0);
});

test('recording a view leaves the resource timestamps untouched', function () {
    $resource = Resource::factory()->image()->create(['updated_at' => now()->subWeek()]);
    $original = $resource->fresh()->updated_at;

    $this->get(route('preview', ['resource' => $resource->code]))->assertOk();

    expect($resource->fresh()->updated_at->toDateTimeString())->toBe($original->toDateTimeString());
});

test('downloading a file records a download', function () {
    Storage::fake();
    $resource = Resource::factory()->create();
    Storage::put($resource->storage_path, 'file contents');

    $this->get(route('download', ['resource' => $resource->code]))->assertOk();

    expect($resource->fresh()->downloads)->toBe(1)
        ->and($resource->fresh()->views)->toBe(0);
});

test('serving the raw file does not record a download', function () {
    Storage::fake();
    $resource = Resource::factory()->create();
    Storage::put($resource->storage_path, 'file contents');

    $this->get(route('raw', ['resource' => $resource->code]))->assertOk();

    expect($resource->fresh()->downloads)->toBe(0)
        ->and($resource->fresh()->views)->toBe(0);
});

test('following a link via the raw route records a download', function () {
    $resource = Resource::factory()->link()->create(['data' => 'https://example.com/destination']);

    $this->get(route('raw', ['resource' => $resource->code]))
        ->assertRedirect('https://example.com/destination');

    expect($resource->fresh()->downloads)->toBe(1)
        ->and($resource->fresh()->views)->toBe(0);
});

test('downloading a link via the download route records a download', function () {
    $resource = Resource::factory()->link()->create(['data' => 'https://example.com/destination']);

    $this->get(route('download', ['resource' => $resource->code]))
        ->assertRedirect('https://example.com/destination');

    expect($resource->fresh()->downloads)->toBe(1);
});
