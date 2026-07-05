<?php

use Illuminate\Support\Facades\Storage;
use XBB\Models\Resource;
use XBB\Models\User;

test('an expired resource preview returns 404 for a guest', function () {
    $resource = Resource::factory()->image()->expired()->create();

    $this->get(route('preview', ['resource' => $resource->code]))->assertNotFound();
});

test('an expired resource preview returns 404 for a non owner', function () {
    $resource = Resource::factory()->image()->expired()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('preview', ['resource' => $resource->code]))
        ->assertNotFound();
});

test('an expired resource is still visible to its owner', function () {
    $owner = User::factory()->create();
    $resource = Resource::factory()->image()->for($owner)->expired()->create();

    $this->actingAs($owner)
        ->get(route('preview', ['resource' => $resource->code]))
        ->assertOk()
        ->assertSee($resource->filename);
});

test('an expired resource is still visible to an admin', function () {
    $resource = Resource::factory()->image()->expired()->create();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get(route('preview', ['resource' => $resource->code]))
        ->assertOk();
});

test('a resource with a future expiration stays public', function () {
    $resource = Resource::factory()->image()->create(['expires_at' => now()->addWeek()]);

    $this->get(route('preview', ['resource' => $resource->code]))->assertOk();
});

test('the serving routes are gated for an expired resource', function () {
    $resource = Resource::factory()->image()->expired()->create();

    $this->get(route('raw', ['resource' => $resource->code]))->assertNotFound();
    $this->get(route('download', ['resource' => $resource->code]))->assertNotFound();
    $this->get(route('thumbnail', ['resource' => $resource->code]))->assertNotFound();
});

test('the owner can still download an expired resource', function () {
    Storage::fake();
    $owner = User::factory()->create();
    $resource = Resource::factory()->for($owner)->expired()->create();
    Storage::put($resource->storage_path, 'content');

    $this->actingAs($owner)
        ->get(route('download', ['resource' => $resource->code]))
        ->assertOk();
});

test('an expired resource preview records no view', function () {
    $resource = Resource::factory()->image()->expired()->create();

    $this->get(route('preview', ['resource' => $resource->code]))->assertNotFound();

    expect($resource->fresh()->views)->toBe(0);
});
