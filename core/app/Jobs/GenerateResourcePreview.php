<?php

namespace App\Jobs;

use App\Actions\Resource\Previews\PdfPreviewGenerator;
use App\Actions\Resource\Previews\PreviewGenerator;
use App\Actions\Resource\Previews\RasterImagePreviewGenerator;
use App\Actions\Resource\Previews\ResourceFile;
use App\Actions\Resource\Previews\SvgPreviewGenerator;
use App\Actions\Resource\Previews\VideoFramePreviewGenerator;
use App\Models\Properties\ResourceType;
use App\Models\Resource;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SergiX44\ImageZen\Draws\Constraint;
use SergiX44\ImageZen\Format;
use Throwable;

class GenerateResourcePreview implements ShouldQueueAfterCommit
{
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $backoff = 30;

    public bool $deleteWhenMissingModels = true;

    /** @var list<class-string<PreviewGenerator>> Ordered: the first supporting generator wins. */
    private const array GENERATORS = [
        SvgPreviewGenerator::class,
        PdfPreviewGenerator::class,
        RasterImagePreviewGenerator::class,
        VideoFramePreviewGenerator::class,
    ];

    public function __construct(public Resource $resource) {}

    public function handle(): void
    {
        $generator = $this->resolveGenerator();

        if ($generator === null) {
            return;
        }

        $file = new ResourceFile($this->resource);
        $image = null;

        try {
            $image = $generator->generate($this->resource, $file);

            if ($image === null) {
                return;
            }

            $maxDimension = (int) config('previews.max_dimension');
            $image->resize($maxDimension, $maxDimension, function (Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            Storage::put(
                "{$this->resource->code}.preview.webp",
                $image->stream(Format::WEBP, (int) config('previews.quality'))
            );

            $this->resource->update([
                'preview_type' => ResourceType::IMAGE,
                'preview_extension' => 'webp',
            ]);
        } catch (Throwable $e) {
            // Deterministic generation failures (corrupt file, missing delegate) must not retry-loop.
            Log::warning('Preview generation failed.', [
                'resource_id' => $this->resource->id,
                'generator' => $generator::class,
                'exception' => $e,
            ]);
        } finally {
            $image?->destroy();
            $file->cleanup();
        }
    }

    private function resolveGenerator(): ?PreviewGenerator
    {
        foreach (self::GENERATORS as $class) {
            $generator = app($class);

            if ($generator->supports($this->resource)) {
                return $generator;
            }
        }

        return null;
    }
}
