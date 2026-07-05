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
    Queue::fake(); // a link reuses the upload pipeline; isolate preview generation
});

test('the upload drawer exposes a shorten-link tab wired to createLink', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Dashboard::class)
        ->assertSee('Shorten link')
        ->assertSeeHtml('wire:model="linkUrl"')
        ->assertSeeHtml('wire:click="createLink"');
});

test('a link is stored as a LINK resource without file-derived columns', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->set('linkUrl', 'https://example.com/some/very/long/path')
        ->set('linkName', 'My link')
        ->call('createLink')
        ->assertHasNoErrors()
        ->assertSet('showUploadDrawer', false)
        ->assertSet('linkUrl', '')
        ->assertSet('linkName', '');

    $resource = Resource::query()->latest('id')->first();

    expect($resource)->not->toBeNull()
        ->and($resource->type)->toBe(ResourceType::LINK)
        ->and($resource->data)->toBe('https://example.com/some/very/long/path')
        ->and($resource->name)->toBe('My link')
        ->and($resource->user_id)->toBe($user->id)
        ->and($resource->extension)->toBeNull()
        ->and($resource->mime)->toBeNull()
        ->and($resource->size)->toBeNull();
});

test('createLink requires a valid http url', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Dashboard::class)
        ->set('linkUrl', 'not-a-url')
        ->call('createLink')
        ->assertHasErrors(['linkUrl']);

    expect(Resource::query()->count())->toBe(0);
});

test('the preview page shows the destination and an open button before redirecting', function () {
    $resource = Resource::factory()->link()->create(['data' => 'https://example.com/destination']);

    $this->get(route('preview', ['resource' => $resource->code]))
        ->assertOk()
        ->assertSee('https://example.com/destination')
        ->assertSee('redirected')
        ->assertSee('Open link')
        ->assertSee($resource->raw_url, false);
});

test('the raw route redirects a link to its destination', function () {
    $resource = Resource::factory()->link()->create(['data' => 'https://example.com/destination']);

    $this->get(route('raw', ['resource' => $resource->code]))
        ->assertRedirect('https://example.com/destination');
});

test('a long compressible url round-trips through the data column and still redirects', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $url = 'https://example.com/'.str_repeat('very-long-repeating-segment/', 60);

    Livewire::test(Dashboard::class)
        ->set('linkUrl', $url)
        ->call('createLink')
        ->assertHasNoErrors();

    $resource = Resource::query()->latest('id')->first()->fresh();

    expect($resource->data)->toBe($url)
        ->and($resource->getRawOriginal('data'))->not->toBe($url);

    $this->get(route('raw', ['resource' => $resource->code]))
        ->assertRedirect($url);
});

test('the download route redirects a link to its destination', function () {
    $resource = Resource::factory()->link()->create(['data' => 'https://example.com/destination']);

    $this->get(route('download', ['resource' => $resource->code]))
        ->assertRedirect('https://example.com/destination');
});

test('the dashboard lists a link with its title and host', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->for($user)->link()->create([
        'name' => 'My titled link',
        'data' => 'https://example.com/path',
    ]);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSee('My titled link')
        ->assertSee('example.com');
});
