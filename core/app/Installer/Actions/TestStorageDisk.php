<?php

namespace XBB\Installer\Actions;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use XBB\Installer\Support\StorageConfig;

/**
 * Verifies a candidate storage configuration with a write/read/delete probe
 * on an on-the-fly disk, without mutating config/filesystems.php.
 */
class TestStorageDisk
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, message: string}
     */
    public function __invoke(array $payload): array
    {
        $probe = 'xbb-install-probe-'.Str::random(12).'.txt';

        try {
            if (($payload['driver'] ?? null) === 'local') {
                $root = (string) ($payload['root'] ?? '');

                if ($root !== '' && ! is_dir($root)) {
                    @mkdir($root, 0775, true);
                }
            }

            $disk = Storage::build(StorageConfig::disk($payload));
            $disk->put($probe, 'xbackbone');
            $contents = $disk->get($probe);
            $disk->delete($probe);

            if ($contents !== 'xbackbone') {
                return ['ok' => false, 'message' => __('The storage probe could not be read back.')];
            }

            return ['ok' => true, 'message' => __('Storage is writable.')];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
