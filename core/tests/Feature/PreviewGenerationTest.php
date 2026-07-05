<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use XBB\Actions\Resource\Previews\VideoFramePreviewGenerator;
use XBB\Jobs\GenerateResourcePreview;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;
use XBB\Models\User;

beforeEach(function () {
    Storage::fake();
});

function svgFixture(): string
{
    return <<<'SVG'
        <?xml version="1.0" encoding="UTF-8"?>
        <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
            <rect width="100" height="100" fill="red"/>
        </svg>
        SVG;
}

function pdfFixture(): string
{
    return <<<'PDF'
        %PDF-1.4
        1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj
        2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj
        3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >> endobj
        trailer << /Root 1 0 R >>
        %%EOF
        PDF;
}

function pngFixture(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 0, 128, 255));

    ob_start();
    imagepng($image);
    imagedestroy($image);

    return ob_get_clean();
}

function imagickCanRasterizePdf(): bool
{
    if (! extension_loaded('imagick') || empty(Imagick::queryFormats('PDF'))) {
        return false;
    }

    // queryFormats only reports the registered coder. Actually rasterizing a PDF needs a
    // working Ghostscript delegate and an ImageMagick policy that permits it; CI images
    // frequently ship without Ghostscript or with PDF disabled in policy.xml. Probe with a
    // real read so the test skips instead of failing when rendering is genuinely unavailable.
    try {
        $imagick = new Imagick;
        $imagick->setResolution(72, 72);
        $imagick->readImageBlob(pdfFixture());
        $imagick->clear();

        return true;
    } catch (Throwable) {
        return false;
    }
}

function storedResource(string $factoryState, string $contents): Resource
{
    $resource = Resource::factory()->{$factoryState}()->create([
        'size' => strlen($contents),
        'fingerprint' => sha1($contents),
    ]);
    Storage::put($resource->storage_path, $contents);

    return $resource;
}

test('always queues preview generation when a file is uploaded', function () {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [
            'file' => UploadedFile::fake()->image('screen.jpg'),
        ])
        ->assertCreated();

    Queue::assertPushed(GenerateResourcePreview::class);
});

test('always queues preview generation for data resources', function () {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [
            'data' => 'just some plain text',
        ])
        ->assertCreated();

    Queue::assertPushed(GenerateResourcePreview::class);
});

test('generates a webp preview for an svg resource', function () {
    $resource = storedResource('svg', svgFixture());

    (new GenerateResourcePreview($resource))->handle();

    Storage::assertExists("{$resource->fingerprint}.preview.webp");
    $resource->refresh();
    expect($resource->preview_type)->toBe(ResourceType::IMAGE)
        ->and($resource->preview_extension)->toBe('webp');
})->skip(fn () => ! extension_loaded('imagick') || empty(Imagick::queryFormats('SVG')), 'imagick SVG support not available');

test('generates a downscaled webp preview for a large raster image', function () {
    config()->set('previews.raster_size_threshold', 1);
    $resource = storedResource('image', pngFixture(2000, 1200));

    (new GenerateResourcePreview($resource))->handle();

    Storage::assertExists("{$resource->fingerprint}.preview.webp");
    [$width, $height] = getimagesizefromstring(Storage::get("{$resource->fingerprint}.preview.webp"));
    expect($width)->toBeLessThanOrEqual(config('previews.max_dimension'))
        ->and($height)->toBeLessThanOrEqual(config('previews.max_dimension'))
        ->and($resource->refresh()->preview_type)->toBe(ResourceType::IMAGE);
});

test('generates a preview from the first pdf page', function () {
    $resource = storedResource('pdf', pdfFixture());

    (new GenerateResourcePreview($resource))->handle();

    Storage::assertExists("{$resource->fingerprint}.preview.webp");
    expect($resource->refresh()->preview_type)->toBe(ResourceType::IMAGE);
})->skip(fn () => ! imagickCanRasterizePdf(), 'imagick PDF rasterization not available');

test('generates a frame preview for a video', function () {
    $videoPath = sys_get_temp_dir().'/xbb-test-video.mp4';
    Process::run("ffmpeg -y -f lavfi -i color=red:s=320x240:d=1 -pix_fmt yuv420p {$videoPath}")->throw();

    $resource = storedResource('video', file_get_contents($videoPath));
    @unlink($videoPath);

    (new GenerateResourcePreview($resource))->handle();

    Storage::assertExists("{$resource->fingerprint}.preview.webp");
    expect($resource->refresh()->preview_type)->toBe(ResourceType::IMAGE);
})->skip(fn () => app(VideoFramePreviewGenerator::class)->createFfmpeg() === null, 'ffmpeg not available');

test('leaves preview fields null when generation fails', function () {
    config()->set('previews.raster_size_threshold', 1);
    $resource = storedResource('image', 'not really a png {{{');

    (new GenerateResourcePreview($resource))->handle();

    Storage::assertMissing("{$resource->fingerprint}.preview.webp");
    $resource->refresh();
    expect($resource->preview_type)->toBeNull()
        ->and($resource->preview_extension)->toBeNull();
});

test('does nothing for resources that do not need a preview', function () {
    $resource = storedResource('image', pngFixture(10, 10));

    (new GenerateResourcePreview($resource))->handle();

    Storage::assertMissing("{$resource->fingerprint}.preview.webp");
    expect($resource->refresh()->preview_type)->toBeNull();
});

test('does nothing and does not crash for a resource without a stored file', function () {
    $resource = Resource::factory()->create(); // FILE type, no file stored

    (new GenerateResourcePreview($resource))->handle();

    Storage::assertMissing("{$resource->fingerprint}.preview.webp");
    expect($resource->refresh()->preview_type)->toBeNull();
});

test('skips gracefully when a matching generator has no stored file to read', function () {
    config()->set('previews.raster_size_threshold', 1);
    // Large image: RasterImagePreviewGenerator matches, but no file was ever stored.
    $resource = Resource::factory()->image()->create(['size' => 5_000_000]);

    (new GenerateResourcePreview($resource))->handle();

    Storage::assertMissing("{$resource->fingerprint}.preview.webp");
    expect($resource->refresh()->preview_type)->toBeNull();
});

test('resolves a pending (FUTURE) resource to null when there is nothing to generate', function () {
    $resource = Resource::factory()->pending()->create(); // FILE type, no stored file

    expect($resource->preview_type)->toBe(ResourceType::FUTURE);

    (new GenerateResourcePreview($resource))->handle();

    Storage::assertMissing("{$resource->fingerprint}.preview.webp");
    expect($resource->refresh()->preview_type)->toBeNull()
        ->and($resource->preview_extension)->toBeNull();
});

test('is idempotent when the job runs twice', function () {
    config()->set('previews.raster_size_threshold', 1);
    $resource = storedResource('image', pngFixture(800, 600));

    $job = new GenerateResourcePreview($resource);
    $job->handle();
    $job->handle();

    Storage::assertExists("{$resource->fingerprint}.preview.webp");
    expect($resource->refresh()->preview_type)->toBe(ResourceType::IMAGE)
        ->and($resource->preview_extension)->toBe('webp');
});
