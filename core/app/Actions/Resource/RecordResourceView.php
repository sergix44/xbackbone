<?php

namespace XBB\Actions\Resource;

use XBB\Models\Resource;

class RecordResourceView
{
    /**
     * Atomically bump the resource's view counter. A view is not a content
     * modification, so the resource's timestamps are intentionally left
     * untouched; the in-memory total is refreshed so the current request can
     * render the up-to-date count.
     */
    public function __invoke(Resource $resource): void
    {
        Resource::withoutTimestamps(fn () => $resource->increment('views'));
    }
}
