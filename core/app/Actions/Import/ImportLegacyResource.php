<?php

namespace App\Actions\Import;

use App\Actions\Resource\StoreResource;
use App\Jobs\GenerateResourcePreview;
use App\Models\Properties\ResourceType;
use App\Models\Resource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Sqids\Sqids;
use Throwable;

class ImportLegacyResource
{
    public function __construct(protected Sqids $genId)
    {
    }

    /**
     * Import a single legacy `uploads` row, plus its physical file, into a {@see Resource}.
     *
     * Mirrors {@see StoreResource}: the file is content-addressed by its
     * sha1 fingerprint and deduplicated, a fresh Sqids code is generated, and the original legacy
     * code is preserved in `legacy_code` for permanent redirects and idempotent re-runs. The
     * original timestamps are kept, and preview generation is opt-in via $withPreviews.
     *
     * @param  object  $row  A legacy `uploads` row (id, user_id, code, filename, storage_path, published, timestamp).
     * @param  int  $newUserId  The resolved owner id in the current application.
     * @param  string  $onMissingFile  Behaviour when the physical file is absent: 'skip' or 'fail'.
     * @return array{resource: \App\Models\Resource|null, action: 'created'|'skipped-duplicate'|'skipped-missing'|'would-create'}
     */
    public function __invoke(
        object $row,
        int $newUserId,
        string $storageRoot,
        bool $dryRun,
        string $onMissingFile,
        bool $withPreviews
    ): array {
        // Idempotency: a previous run already imported this legacy upload.
        if (Resource::query()->where('legacy_code', $row->code)->exists()) {
            return ['resource' => null, 'action' => 'skipped-duplicate'];
        }

        $absolute = $this->resolvePath($storageRoot, (string) $row->storage_path);

        if ($absolute === null) {
            return $this->onMissing($row, $onMissingFile);
        }

        if ($dryRun) {
            return ['resource' => null, 'action' => 'would-create'];
        }

        $fingerprint = sha1_file($absolute);

        if ($fingerprint === false) {
            return $this->onMissing($row, $onMissingFile);
        }

        $mime = mime_content_type($absolute) ?: 'application/octet-stream';
        $type = ResourceType::fromMime($mime);
        $extension = $this->resolveExtension((string) $row->filename, (string) $row->storage_path);
        $timestamp = $this->parseTimestamp($row->timestamp ?? null);

        return DB::transaction(function () use (
            $row,
            $newUserId,
            $absolute,
            $fingerprint,
            $mime,
            $type,
            $extension,
            $timestamp,
            $withPreviews
        ) {
            // Content-addressed deduplication: identical bytes are stored once and shared. A
            // duplicate may already carry a generated preview, which the new resource inherits.
            $existing = Resource::query()->where('fingerprint', $fingerprint)->first();

            $resource = Resource::query()->create([
                'type' => $type,
                'user_id' => $newUserId,
                'filename' => $row->filename,
                'size' => filesize($absolute),
                'mime' => $mime,
                'extension' => $extension,
                'is_private' => ! (bool) $row->published,
                'fingerprint' => $fingerprint,
                'legacy_code' => $row->code,
                'preview_type' => $existing
                    ? $existing->preview_type
                    : ($withPreviews ? ResourceType::FUTURE : null),
                'preview_extension' => $existing?->preview_extension,
            ]);

            if (! $existing) {
                $this->storeFile($absolute, $fingerprint, (int) $row->id);
            }

            $resource->forceFill([
                'code' => $this->genId->encode([$newUserId, $resource->id]),
                'published_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->save();

            if ($withPreviews) {
                GenerateResourcePreview::dispatch($resource);
            }

            return ['resource' => $resource, 'action' => 'created'];
        });
    }

    private function storeFile(string $absolute, string $fingerprint, int $legacyId): void
    {
        $stream = fopen($absolute, 'rb');

        try {
            if (! Storage::put($fingerprint, $stream)) {
                throw new RuntimeException("Failed to store the file for upload #{$legacyId}.");
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @return array{resource: null, action: 'skipped-missing'}
     */
    private function onMissing(object $row, string $onMissingFile): array
    {
        if ($onMissingFile === 'fail') {
            throw new RuntimeException("Legacy file not found for upload #{$row->id}: {$row->storage_path}");
        }

        return ['resource' => null, 'action' => 'skipped-missing'];
    }

    /**
     * Resolve the absolute on-disk path of a legacy file, rejecting anything that
     * escapes the storage root. Returns null when the file does not exist.
     */
    private function resolvePath(string $storageRoot, string $relative): ?string
    {
        $root = realpath($storageRoot);

        if ($root === false) {
            return null;
        }

        $real = realpath($root.DIRECTORY_SEPARATOR.ltrim($relative, '/\\'));

        if ($real === false || ! is_file($real) || ! str_starts_with($real, $root.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $real;
    }

    private function resolveExtension(string $filename, string $storagePath): ?string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION)
            ?: pathinfo($storagePath, PATHINFO_EXTENSION);

        return $extension !== '' ? strtolower($extension) : null;
    }

    private function parseTimestamp(?string $value): Carbon
    {
        if ($value === null || $value === '' || str_starts_with($value, '0000-00-00')) {
            return now();
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return now();
        }
    }
}
