<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use XBB\Actions\Resource\StoreResource;
use XBB\Exceptions\QuotaExceededException;
use XBB\Livewire\Dashboard;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;
use XBB\Models\User;

beforeEach(function () {
    Storage::fake();
    Queue::fake(); // isolate quota behaviour from preview generation
});

/** Quota is not mass-assignable, so set it directly like the admin actions do. */
function withQuota(User $user, int $bytes): User
{
    $user->quota = $bytes;
    $user->save();

    return $user;
}

test('the action rejects an upload that would exceed the quota', function () {
    $user = withQuota(User::factory()->create(), 100 * 1024);
    Resource::factory()->for($user)->create(['size' => 90 * 1024]);

    $store = app(StoreResource::class);

    expect(fn () => $store($user, UploadedFile::fake()->create('big.bin', 20)))
        ->toThrow(QuotaExceededException::class);

    // Nothing was persisted beyond the seeded resource.
    expect($user->resources()->count())->toBe(1);
});

test('the action allows an upload up to the quota boundary', function () {
    $user = withQuota(User::factory()->create(), 100 * 1024);
    Resource::factory()->for($user)->create(['size' => 60 * 1024]);

    $resource = app(StoreResource::class)($user, UploadedFile::fake()->create('fits.bin', 40));

    expect($resource)->toBeInstanceOf(Resource::class)
        ->and($user->resources()->count())->toBe(2);
});

test('an unlimited quota never blocks an upload', function () {
    // Factory default quota is -1 (unlimited).
    $user = User::factory()->create();

    $resource = app(StoreResource::class)($user, UploadedFile::fake()->create('huge.bin', 500));

    expect($resource)->toBeInstanceOf(Resource::class);
});

test('a link is allowed even when the user is over quota', function () {
    $user = withQuota(User::factory()->create(), 10);
    Resource::factory()->for($user)->create(['size' => 50]);

    $resource = app(StoreResource::class)($user, data: 'https://example.com/some/long/path');

    expect($resource->type)->toBe(ResourceType::LINK)
        ->and($resource->size)->toBeNull();
});

test('the API returns 413 when the upload exceeds the quota', function () {
    $user = withQuota(User::factory()->create(), 100 * 1024);
    Resource::factory()->for($user)->create(['size' => 90 * 1024]);

    $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [
            'file' => UploadedFile::fake()->create('big.bin', 20),
        ])
        ->assertStatus(413);

    expect($user->resources()->count())->toBe(1);
});

test('the API accepts an upload within the quota', function () {
    $user = withQuota(User::factory()->create(), 100 * 1024);

    $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [
            'file' => UploadedFile::fake()->create('small.bin', 10),
        ])
        ->assertCreated();
});

test('the dashboard upload is blocked when it exceeds the quota', function () {
    $user = withQuota(User::factory()->create(), 100 * 1024);
    Resource::factory()->for($user)->create(['size' => 90 * 1024]);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->set('files', [UploadedFile::fake()->create('big.bin', 20)])
        ->call('saveUpload', 0);

    // Only the seeded resource exists; the over-quota upload was rejected.
    expect($user->resources()->count())->toBe(1);
});

test('a paste is blocked when it exceeds the quota', function () {
    $user = withQuota(User::factory()->create(), 10);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->call('createPaste', 'this content is definitely more than ten bytes');

    expect($user->resources()->count())->toBe(0);
});
