<?php

namespace XBB\Actions\Resource\Previews;

use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;
use Imagick;
use ImagickPixel;
use SergiX44\ImageZen\Backend;
use SergiX44\ImageZen\Image;

class SvgPreviewGenerator implements PreviewGenerator
{
    public function supports(Resource $resource): bool
    {
        return $resource->type === ResourceType::IMAGE
            && str_starts_with($resource->mime, 'image/svg')
            && Backend::IMAGICK->getDriver()->isAvailable()
            && ! empty(Imagick::queryFormats('SVG'));
    }

    public function generate(Resource $resource, ResourceFile $file): ?Image
    {
        $density = config('previews.density');

        $imagick = new Imagick;
        $imagick->setBackgroundColor(new ImagickPixel('transparent'));
        $imagick->setResolution($density, $density);
        $imagick->readImageFile($file->stream());
        $imagick->setImageFormat('png');

        return Image::make($imagick);
    }
}
