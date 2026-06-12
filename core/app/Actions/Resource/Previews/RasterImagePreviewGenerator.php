<?php

namespace App\Actions\Resource\Previews;

use App\Models\Properties\ResourceType;
use App\Models\Resource;
use Imagick;
use SergiX44\ImageZen\Backend;
use SergiX44\ImageZen\Image;

class RasterImagePreviewGenerator implements PreviewGenerator
{
    public function supports(Resource $resource): bool
    {
        if ($resource->type !== ResourceType::IMAGE || str_starts_with($resource->mime, 'image/svg')) {
            return false;
        }

        return ! $resource->type->isDisplayable($resource->mime)
            || $resource->size > config('previews.raster_size_threshold');
    }

    public function generate(Resource $resource, ResourceFile $file): ?Image
    {
        if (Backend::IMAGICK->getDriver()->isAvailable()) {
            $imagick = new Imagick;
            $imagick->readImageFile($file->stream());

            return Image::make($imagick);
        }

        // GD has no streaming reader: decode the original bytes in-memory.
        return Image::make(stream_get_contents($file->stream()), Backend::GD);
    }
}
