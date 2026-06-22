<?php

namespace App\Actions\Resource;

use App\Jobs\GenerateResourcePreview;
use App\Models\Properties\ResourceType;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Sqids\Sqids;

class StoreResource
{
    public function __construct(protected Sqids $genId) {}

    public function __invoke(
        User $user,
        ?UploadedFile $file = null,
        ?string $name = null,
        ?string $data = null
    ): Resource {
        if (! $file && ! $data) {
            throw new InvalidArgumentException('Cannot store a resource without a file or data.');
        }

        if (! $name && $file) {
            $name = $file?->getClientOriginalName() ?? $file?->hashName();
        }

        $fingerprint = $file ? sha1_file($file->getRealPath()) : sha1($data);
        $type = $this->findType($file, $data);

        return DB::transaction(function () use ($user, $file, $name, $data, $fingerprint, $type) {
            // Content-addressed deduplication: an existing resource with the same fingerprint
            // already has the physical file (and possibly a preview) stored.
            $existing = Resource::query()->where('fingerprint', $fingerprint)->first();

            // A link has no physical file, so the file-derived columns stay empty.
            $isLink = $type === ResourceType::LINK;

            if ($isLink && !$name) {
                $name = parse_url($data, PHP_URL_HOST);
            }

            $resource = Resource::query()->create([
                'type' => $type,
                'user_id' => $user->id,
                'filename' => $file?->getClientOriginalName(),
                'size' => $isLink ? null : ($file?->getSize() ?? strlen($data)),
                'mime' => $isLink ? null : ($file?->getMimeType() ?? 'text/plain'),
                'extension' => $isLink ? null : ($this->fromFilename($file) ?? $file?->extension() ?? 'txt'),
                'name' => $name,
                'data' => $data,
                'fingerprint' => $fingerprint,
                // Inherit the preview from a duplicate so the UI has one immediately.
                'preview_type' => $existing?->preview_type ?? ResourceType::FUTURE,
                'preview_extension' => $existing?->preview_extension,
            ]);

            if (! $resource) {
                throw new InvalidArgumentException('Failed to store the resource.');
            }

            $code = $this->genId->encode([$user->id, $resource->id]);

            // Only write the file when this content isn't already stored.
            if ($file && ! $existing) {
                $stream = fopen($file->getRealPath(), 'rb');
                if (! Storage::put($fingerprint, $stream)) {
                    throw new RuntimeException('Failed to store the file.');
                }
            }

            $resource->update([
                'code' => $code,
                'published_at' => now(),
            ]);

            GenerateResourcePreview::dispatch($resource);

            return $resource;
        });
    }

    private function findType(?UploadedFile $file, ?string $data): ResourceType
    {
        if ($file) {
            return ResourceType::fromMime($file->getMimeType());
        }

        if ($data) {
            return ResourceType::fromValue($data);
        }

        return ResourceType::FILE;
    }

    private function fromFilename(?UploadedFile $file): ?string
    {
        $originalExtension = $file?->getClientOriginalExtension();

        if (empty($originalExtension)) {
            return null;
        }

        return strtolower($originalExtension);
    }
}
