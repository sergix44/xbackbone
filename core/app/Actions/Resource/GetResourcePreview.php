<?php

namespace App\Actions\Resource;

use App\Models\Properties\ResourceType;
use App\Models\Resource;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GetResourcePreview
{
    public function __invoke(Resource $resource): StreamedResponse
    {
        if ($resource->has_preview) {
            return Storage::response($resource->preview_path);
        }

        // Small displayable images never get a generated preview: serve the original file
        if ($resource->type === ResourceType::IMAGE && $resource->is_displayable) {
            return Storage::response($resource->storage_path, $resource->filename);
        }

        abort(404);
    }
}
