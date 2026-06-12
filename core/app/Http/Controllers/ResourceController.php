<?php

namespace App\Http\Controllers;

use App\Actions\Resource\GetResourcePreview;
use App\Models\Resource;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResourceController extends Controller
{
    public function raw(Resource $resource): StreamedResponse
    {
        return Storage::response($resource->storage_path, $resource->filename);
    }

    public function preview(Resource $resource, GetResourcePreview $getResourcePreview)
    {
        return $getResourcePreview($resource);
    }

    public function download(Resource $resource): StreamedResponse
    {
        return Storage::response($resource->storage_path, $resource->filename, disposition: 'attachment');
    }
}
