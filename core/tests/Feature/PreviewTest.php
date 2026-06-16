<?php

use App\Models\Resource;
use Illuminate\Support\Facades\Storage;

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

test('preview page wires the above/below fold width sync', function () {
    $resource = Resource::factory()->image()->create();

    $this->get(route('preview.ext', ['resource' => $resource->code, 'ext' => $resource->extension]))
        ->assertOk()
        ->assertSee('aboveBelowFoldSync()', false)
        ->assertSee('x-ref="card"', false)
        ->assertSee('x-ref="media"', false);
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

test('preview page offers a download for non displayable resources', function () {
    $resource = Resource::factory()->create([
        'extension' => 'zip',
        'mime' => 'application/zip',
    ]);

    $this->get(route('preview', ['resource' => $resource->code]))
        ->assertOk()
        ->assertSee($resource->filename)
        ->assertSee($resource->download_url, false)
        ->assertSee('w-32 h-32 '.$resource->icon_color, false);

    expect($resource->icon_color)->toBe('text-warning');
});

test('preview page renders highlighted text for textual resources', function () {
    Storage::fake();

    $content = "{\n  \"hello\": \"world\"\n}";
    $resource = Resource::factory()->text()->create(['size' => strlen($content)]);
    Storage::put($resource->storage_path, $content);

    $this->get(route('preview.ext', ['resource' => $resource->code, 'ext' => $resource->extension]))
        ->assertOk()
        ->assertSee("codeHighlighter('{$resource->extension}')", false)
        ->assertSee(e($content), false)
        ->assertSeeInOrder(['<div>1</div>', '<div>2</div>', '<div>3</div>'], false)
        ->assertDontSee('No preview available');
});

test('preview page offers a download instead of inlining oversized text', function () {
    Storage::fake();

    $resource = Resource::factory()->text()->create(['size' => 5 * 1024 * 1024]);
    Storage::put($resource->storage_path, 'should not be rendered');

    $this->get(route('preview.ext', ['resource' => $resource->code, 'ext' => $resource->extension]))
        ->assertOk()
        ->assertSee('too large to preview')
        ->assertDontSee('should not be rendered');
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
