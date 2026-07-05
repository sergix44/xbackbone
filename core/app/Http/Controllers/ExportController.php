<?php

namespace XBB\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use XBB\Models\Resource;
use XBB\Models\User;
use ZipStream\ZipStream;

class ExportController extends Controller
{
    /**
     * Stream a ZIP archive of every file the authenticated user has uploaded.
     *
     * The archive is built on the fly and flushed straight to the browser, so
     * neither the whole archive nor the individual files are buffered in memory
     * or on disk. Files are read as streams from the storage disk, which keeps
     * the export working unchanged on local and remote (S3) disks alike.
     */
    public function download(): StreamedResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $filename = Str::slug($user->name).'-'.now()->timestamp.'-export.zip';

        return response()->streamDownload(function () use ($user) {
            // Laravel's streamed response sets the HTTP headers and streams the
            // echoed output itself, so ZipStream must not send headers of its own.
            $zip = new ZipStream(sendHttpHeaders: false);

            $usedNames = [];

            $user->resources()
                ->whereNotNull('fingerprint')
                ->cursor()
                ->each(function (Resource $resource) use ($zip, &$usedNames) {
                    $stream = Storage::readStream($resource->storage_path);

                    if ($stream === null) {
                        return; // physical file is missing — skip it, like the legacy export did
                    }

                    $zip->addFileFromStream($this->resolveEntryName($resource, $usedNames), $stream);

                    fclose($stream);
                });

            $zip->finish();
        }, $filename, ['Content-Type' => 'application/zip']);
    }

    /**
     * Resolve a unique archive entry name for the resource, falling back to the
     * stored name (or code) when the original filename is missing and
     * disambiguating duplicate filenames with the resource code.
     *
     * @param  array<string, true>  $usedNames
     */
    private function resolveEntryName(Resource $resource, array &$usedNames): string
    {
        $name = $resource->filename ?: trim(($resource->name ?: $resource->code).'.'.$resource->extension, '.');

        if (isset($usedNames[$name])) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $base = $extension !== '' ? Str::beforeLast($name, '.'.$extension) : $name;
            $name = $extension !== '' ? "{$base}-{$resource->code}.{$extension}" : "{$name}-{$resource->code}";
        }

        $usedNames[$name] = true;

        return $name;
    }
}
