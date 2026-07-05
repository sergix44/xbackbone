<?php

namespace XBB\Actions\Resource\Previews;

use Imagick;
use SergiX44\ImageZen\Backend;
use SergiX44\ImageZen\Image;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;

class PdfPreviewGenerator implements PreviewGenerator
{
    public function supports(Resource $resource): bool
    {
        return $resource->type === ResourceType::PDF
            && Backend::IMAGICK->getDriver()->isAvailable()
            && ! empty(Imagick::queryFormats('PDF'));
    }

    public function generate(Resource $resource, ResourceFile $file): ?Image
    {
        $density = config('previews.density');

        // Page selection ([0]) requires a path, so only the first page is decoded
        // instead of streaming (and rasterizing) the whole document.
        $imagick = new Imagick;
        $imagick->setResolution($density, $density);
        $imagick->readImage($file->localPath().'[0]');
        $imagick->setImageBackgroundColor('white');
        $imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        $imagick->setImageFormat('png');

        return Image::make($imagick);
    }
}
