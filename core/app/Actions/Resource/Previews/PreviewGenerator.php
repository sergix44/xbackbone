<?php

namespace App\Actions\Resource\Previews;

use App\Models\Resource;
use SergiX44\ImageZen\Image;

interface PreviewGenerator
{
    /**
     * Whether this generator handles the given resource and its tooling is available.
     */
    public function supports(Resource $resource): bool;

    /**
     * Produce an in-memory image from the original file, or null to skip.
     */
    public function generate(Resource $resource, ResourceFile $file): ?Image;
}
