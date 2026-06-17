<?php

namespace App\Http\Controllers;

use App\Actions\Resource\GetResourcePreview;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResourceController extends Controller
{
    public function raw(Resource $resource): StreamedResponse
    {
        return Storage::response($resource->storage_path, $resource->filename);
    }

    public function thumbnail(Request $request, Resource $resource, GetResourcePreview $getResourcePreview)
    {
        if ($resource->preview_is_pending && $request->has('probe')) {
            abort(425);
        }

        return $getResourcePreview($resource, $request->input('w'), $request->input('h'), $request->input('q')) ?? abort(404);
    }

    public function download(Resource $resource): StreamedResponse
    {
        return Storage::response($resource->storage_path, $resource->filename, disposition: 'attachment');
    }
}
