<?php

namespace App\Actions\Resource;

use App\Models\Properties\ResourceType;
use App\Models\Resource;
use Illuminate\Support\Facades\Storage;
use SergiX44\ImageZen\Draws\Constraint;
use SergiX44\ImageZen\Draws\Position;
use SergiX44\ImageZen\Format;
use SergiX44\ImageZen\Image;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GetResourcePreview
{
    public function __invoke(Resource $resource, ?int $width = null, ?int $height = null, ?int $quality = null): ?StreamedResponse
    {
        if ($resource->has_preview) {
            return Storage::response($resource->preview_path);
        }

        // Small displayable images: resize on-demand, or serve the original when no size is requested.
        if ($resource->type === ResourceType::IMAGE && $resource->is_displayable) {

            if ($width === null && $width === null && $quality === null) {
                return Storage::response($resource->storage_path, $resource->filename);
            }

            $image = Image::make(Storage::read($resource->storage_path))
                ->resize($width, $height, function (Constraint $constraint) {
                    $constraint->aspectRatio();
                })
                ->resizeCanvas($width, $height, Position::CENTER);
            $stream = $image->stream(Format::WEBP, $quality ?? 90);
            $image->destroy();

            return new StreamedResponse(function () use ($stream) {
                $res = $stream->detach();
               fpassthru($res);
               fclose($res);
            }, 200, [
                'Content-Type' => 'image/webp',
                'Content-Length' => $stream->getSize(),
                'Content-Disposition' => 'inline; filename="'.pathinfo($resource->filename, PATHINFO_FILENAME).'.webp"',
            ]);
        }

        return null;
    }
}
