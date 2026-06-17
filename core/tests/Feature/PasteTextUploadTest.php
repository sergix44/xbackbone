<?php

use App\Livewire\Dashboard;
use App\Models\Properties\ResourceType;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake();
    Queue::fake(); // the paste reuses the upload pipeline; isolate preview generation
});

test('the upload drawer exposes a paste-text tab wired to submitPaste', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Dashboard::class)
        ->assertSee('Paste text')
        ->assertSeeHtml('x-model="pasteContent"')
        ->assertSeeHtml('submitPaste()');
});

test('pasted text is stored as a displayable text resource readable from storage', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // The drawer turns the pasted text into a File and runs it through the same
    // upload pipeline as a dropped file: $wire.upload(...) then saveUpload().
    $content = "first line\nsecond line\n";

    Livewire::test(Dashboard::class)
        ->set('files.0', UploadedFile::fake()->createWithContent('paste-20260617-101500.txt', $content))
        ->call('saveUpload', 0)
        ->assertHasNoErrors();

    $resource = Resource::query()->latest('id')->first();

    expect($resource)->not->toBeNull()
        ->and($resource->type)->toBe(ResourceType::TEXT)
        ->and($resource->extension)->toBe('txt')
        ->and($resource->user_id)->toBe($user->id)
        ->and($resource->is_displayable)->toBeTrue();

    // The content is content-addressed in storage, so the preview/raw routes can serve it.
    expect(Storage::get($resource->storage_path))->toBe($content);
});
