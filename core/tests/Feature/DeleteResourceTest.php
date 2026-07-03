<?php

use XBB\Actions\Resource\DeleteResource;
use XBB\Livewire\Dashboard;
use XBB\Models\Resource;
use XBB\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake();
});

test('deleting a resource that has a duplicate keeps the shared file', function () {
    $fingerprint = sha1('shared-content');
    $a = Resource::factory()->create(['fingerprint' => $fingerprint]);
    $b = Resource::factory()->create(['fingerprint' => $fingerprint]);
    Storage::put($a->storage_path, 'shared-content');

    app(DeleteResource::class)($a);

    expect(Resource::find($a->id))->toBeNull()
        ->and(Resource::find($b->id))->not->toBeNull();
    Storage::assertExists($b->storage_path);
});

test('deleting the last reference removes the file and its preview', function () {
    $resource = Resource::factory()->image()->withPreview()->create();
    Storage::put($resource->storage_path, 'png-bytes');
    Storage::put($resource->preview_path, 'webp-bytes');

    app(DeleteResource::class)($resource);

    expect(Resource::find($resource->id))->toBeNull();
    Storage::assertMissing($resource->storage_path);
    Storage::assertMissing($resource->preview_path);
});

test('the api endpoint lets an owner delete their resource', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->for($user)->create();
    Storage::put($resource->storage_path, 'bytes');

    $this->actingAs($user)
        ->deleteJson(route('api.v1.resources.destroy', $resource->code))
        ->assertNoContent();

    expect(Resource::find($resource->id))->toBeNull();
    Storage::assertMissing($resource->storage_path);
});

test('the api endpoint forbids deleting another users resource', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $resource = Resource::factory()->for($owner)->create();

    $this->actingAs($other)
        ->deleteJson(route('api.v1.resources.destroy', $resource->code))
        ->assertForbidden();

    expect(Resource::find($resource->id))->not->toBeNull();
});

test('the api endpoint requires authentication', function () {
    $resource = Resource::factory()->create();

    $this->deleteJson(route('api.v1.resources.destroy', $resource->code))
        ->assertUnauthorized();
});

test('the dashboard deletes an owned resource', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->for($user)->create();
    Storage::put($resource->storage_path, 'bytes');

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->call('confirmDelete', $resource->id)
        ->assertSet('confirmingDelete', true)
        ->call('deleteResource')
        ->assertSet('confirmingDelete', false)
        ->assertHasNoErrors();

    expect(Resource::find($resource->id))->toBeNull();
});

test('the dashboard refuses to delete another users resource', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $resource = Resource::factory()->for($owner)->create();

    $this->actingAs($other);

    Livewire::test(Dashboard::class)
        ->call('confirmDelete', $resource->id)
        ->call('deleteResource');

    expect(Resource::find($resource->id))->not->toBeNull();
});
