<?php

namespace App\Actions\Resource\Previews;

use App\Models\Resource;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use RuntimeException;

/**
 * Lazy access to a resource's stored file for preview generation.
 *
 * Prefers streaming the original file directly (no full download) and only
 * materializes a temporary local copy when a generator needs a real filesystem
 * path on a non-local disk. Any opened streams and temporary copies are released
 * via {@see cleanup()}.
 */
class ResourceFile
{
    /** @var list<resource> */
    private array $streams = [];

    /** @var list<string> */
    private array $tempPaths = [];

    public function __construct(public readonly Resource $resource) {}

    /**
     * A readable stream to the original file. Closed automatically on cleanup().
     *
     * @return resource
     */
    public function stream()
    {
        $stream = Storage::readStream($this->resource->storage_path);

        if (! is_resource($stream)) {
            throw new RuntimeException("Unable to read resource file [{$this->resource->storage_path}].");
        }

        return $this->streams[] = $stream;
    }

    /**
     * A local filesystem path to the original file. Uses the disk path directly
     * when the disk is local (no copy); otherwise materializes a temporary copy.
     */
    public function localPath(): string
    {
        $disk = Storage::disk();

        if ($disk->getAdapter() instanceof LocalFilesystemAdapter) {
            return $disk->path($this->resource->storage_path);
        }

        $path = sys_get_temp_dir().'/xbb-preview-'.bin2hex(random_bytes(8)).'.'.$this->resource->extension;
        $target = fopen($path, 'wb');
        stream_copy_to_stream($this->stream(), $target);
        fclose($target);

        return $this->tempPaths[] = $path;
    }

    public function cleanup(): void
    {
        foreach ($this->streams as $stream) {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        foreach ($this->tempPaths as $path) {
            @unlink($path);
        }

        $this->streams = [];
        $this->tempPaths = [];
    }
}
