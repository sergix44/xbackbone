<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use XBB\Actions\Resource\StoreResource;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;
use XBB\Models\User;

beforeEach(function () {
    Storage::fake();
    Queue::fake(); // isolate dedup behaviour from preview generation
});

test('uploading identical files stores the physical file only once', function () {
    $user = User::factory()->create();
    $store = app(StoreResource::class);

    $first = $store($user, UploadedFile::fake()->createWithContent('a.txt', 'same bytes'));
    $second = $store($user, UploadedFile::fake()->createWithContent('b.txt', 'same bytes'));

    expect($first->fingerprint)->toBe($second->fingerprint)
        ->and($first->id)->not->toBe($second->id)
        ->and($first->code)->not->toBe($second->code);

    Storage::assertExists($first->storage_path);
    expect(Storage::allFiles())->toHaveCount(1);
});

test('uploading different content stores separate files', function () {
    $user = User::factory()->create();
    $store = app(StoreResource::class);

    $store($user, UploadedFile::fake()->createWithContent('a.txt', 'aaa'));
    $store($user, UploadedFile::fake()->createWithContent('b.txt', 'bbb'));

    expect(Storage::allFiles())->toHaveCount(2);
});

test('a duplicate inherits the preview of the existing resource', function () {
    $user = User::factory()->create();
    $content = 'dup content';

    Resource::factory()->image()->withPreview()->create([
        'fingerprint' => sha1($content),
    ]);

    $resource = app(StoreResource::class)(
        $user,
        UploadedFile::fake()->createWithContent('copy.png', $content)
    );

    expect($resource->preview_type)->toBe(ResourceType::IMAGE)
        ->and($resource->preview_extension)->toBe('webp');
});
