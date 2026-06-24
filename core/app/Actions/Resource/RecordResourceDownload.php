<?php

namespace App\Actions\Resource;

use App\Models\Resource;

class RecordResourceDownload
{
    /**
     * Atomically bump the resource's download counter. For links a download is
     * the redirect to the destination. A download is not a content
     * modification, so the resource's timestamps are intentionally left
     * untouched; the in-memory total is refreshed so the current request can
     * render the up-to-date count.
     */
    public function __invoke(Resource $resource): void
    {
        Resource::withoutTimestamps(fn () => $resource->increment('downloads'));
    }
}
