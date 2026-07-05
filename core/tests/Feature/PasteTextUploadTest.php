<?php

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use XBB\Livewire\Dashboard;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;
use XBB\Models\User;

beforeEach(function () {
    Storage::fake();
    Queue::fake(); // isolate preview generation
});

test('the upload drawer exposes a paste-text tab wired to submitPaste', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Dashboard::class)
        ->assertSee('Paste text')
        ->assertSeeHtml('x-model="pasteContent"')
        ->assertSeeHtml('submitPaste()')
        ->assertSeeHtml('$wire.createPaste(content, name)');
});

test('a paste is stored in the data column as a displayable text resource', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $content = "first line\nsecond line\n";

    Livewire::test(Dashboard::class)
        ->call('createPaste', $content, 'snippet.js')
        ->assertHasNoErrors()
        ->assertSet('showUploadDrawer', false);

    $resource = Resource::query()->latest('id')->first();

    expect($resource)->not->toBeNull()
        ->and($resource->type)->toBe(ResourceType::TEXT)
        ->and($resource->mime)->toBe('text/plain')
        ->and($resource->extension)->toBe('js')
        ->and($resource->size)->toBe(strlen($content))
        ->and($resource->user_id)->toBe($user->id)
        ->and($resource->is_displayable)->toBeTrue()
        ->and($resource->has_inline_content)->toBeTrue()
        ->and($resource->data)->toBe($content);

    // No physical file is written for a paste; the content lives in the column.
    expect(Storage::get($resource->storage_path))->toBeNull();
});

test('createPaste requires non-empty content', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Dashboard::class)
        ->call('createPaste', '', null)
        ->assertHasErrors(['content']);

    expect(Resource::query()->count())->toBe(0);
});

test('the raw route serves paste content as text/plain', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $content = "echo 'hello';\n";

    Livewire::test(Dashboard::class)
        ->call('createPaste', $content, 'script.sh')
        ->assertHasNoErrors();

    $resource = Resource::query()->latest('id')->first();

    $response = $this->get(route('raw', ['resource' => $resource->code]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->getContent())->toBe($content);
});

test('the download route serves paste content as an attachment', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $content = "downloadable paste\n";

    Livewire::test(Dashboard::class)
        ->call('createPaste', $content, 'notes.txt')
        ->assertHasNoErrors();

    $resource = Resource::query()->latest('id')->first();

    $response = $this->get(route('download', ['resource' => $resource->code]));

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($response->headers->get('Content-Disposition'))->toContain('notes.txt');
});

test('a large paste is stored compressed and round-trips back to the original', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Highly compressible content so the packed form is smaller than the original.
    $content = str_repeat("the quick brown fox jumps over the lazy dog\n", 500);

    Livewire::test(Dashboard::class)
        ->call('createPaste', $content, 'big.txt')
        ->assertHasNoErrors();

    $resource = Resource::query()->latest('id')->first()->fresh();

    expect($resource->data)->toBe($content)
        ->and($resource->getRawOriginal('data'))->not->toBe($content)
        ->and(strlen($resource->getRawOriginal('data')))->toBeLessThan(strlen($content));
});
