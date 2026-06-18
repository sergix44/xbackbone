<?php

use App\Livewire\Dashboard;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake();
});

test('has_preview is true only for a real generated preview', function () {
    expect(Resource::factory()->create()->has_preview)->toBeFalse()
        ->and(Resource::factory()->pending()->create()->has_preview)->toBeFalse()
        ->and(Resource::factory()->withPreview()->create()->has_preview)->toBeTrue();
});

test('preview_is_pending is true only while a preview is being generated', function () {
    expect(Resource::factory()->pending()->create()->preview_is_pending)->toBeTrue()
        ->and(Resource::factory()->create()->preview_is_pending)->toBeFalse()
        ->and(Resource::factory()->withPreview()->create()->preview_is_pending)->toBeFalse();
});

test('the gallery card polls for a pending video preview', function () {
    $user = User::factory()->create();
    Resource::factory()->for($user)->video()->pending()->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSeeHtml('pendingPreview(');
});

test('each gallery card is keyed so morphing keeps its preview poller bound to the right resource', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->for($user)->video()->pending()->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSeeHtml('wire:key="resource-'.$resource->id.'"');
});

test('the gallery card shows the thumbnail once the preview is ready', function () {
    $user = User::factory()->create();
    Resource::factory()->for($user)->video()->withPreview()->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSeeHtml('thumbnail')
        ->assertDontSeeHtml('pendingPreview(');
});

test('a resource that will never get a preview does not poll', function () {
    $user = User::factory()->create();
    Resource::factory()->for($user)->create(); // FILE, preview_type null

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertDontSeeHtml('pendingPreview(');
});

test('the thumbnail endpoint 404s a plain request while a video preview is pending', function () {
    $resource = Resource::factory()->video()->pending()->create();

    $this->get(route('thumbnail', $resource->code))
        ->assertNotFound();
});

test('the thumbnail endpoint returns 425 to a probe while a video preview is pending', function () {
    $resource = Resource::factory()->video()->pending()->create();

    $this->get(route('thumbnail', ['resource' => $resource->code, 'probe' => 1]))
        ->assertStatus(425);
});
