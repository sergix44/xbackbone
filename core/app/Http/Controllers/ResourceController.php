<?php

namespace App\Http\Controllers;

use App\Actions\Resource\DeleteResource;
use App\Actions\Resource\GetResourcePreview;
use App\Actions\Resource\RecordResourceDownload;
use App\Models\Properties\ResourceType;
use App\Models\Resource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResourceController extends Controller
{
    public function raw(Resource $resource, RecordResourceDownload $recordResourceDownload): Response|StreamedResponse|RedirectResponse
    {
        if ($resource->type === ResourceType::LINK) {
            // For a link there is no file to serve: following it is a redirect,
            // which counts as a download.
            $recordResourceDownload($resource);

            return redirect()->away($resource->data);
        }

        if ($resource->has_inline_content) {
            return response($resource->data, 200, [
                'Content-Type' => $resource->mime ?: 'text/plain',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return Storage::response($resource->storage_path, $resource->filename);
    }

    public function thumbnail(Request $request, Resource $resource, GetResourcePreview $getResourcePreview)
    {
        if ($resource->preview_is_pending && $request->has('probe')) {
            abort(425);
        }

        return $getResourcePreview($resource, $request->input('w'), $request->input('h'), $request->input('q')) ?? abort(404);
    }

    public function download(Resource $resource, RecordResourceDownload $recordResourceDownload): Response|StreamedResponse|RedirectResponse
    {
        $recordResourceDownload($resource);

        if ($resource->type === ResourceType::LINK) {
            return redirect()->away($resource->data);
        }

        if ($resource->has_inline_content) {
            return response($resource->data, 200, [
                'Content-Type' => $resource->mime ?: 'text/plain',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Disposition' => 'attachment; filename="'.addslashes($resource->filename ?? $resource->code).'"',
            ]);
        }

        return Storage::response($resource->storage_path, $resource->filename, disposition: 'attachment');
    }

    /**
     * Delete a resource through a permanent signed URL, as used by ShareX's
     * "DeletionURL". The route's `signed` middleware validates the signature, so
     * possession of the URL is the authorization — no ownership check is needed.
     */
    public function delete(Resource $resource, DeleteResource $deleteResource): RedirectResponse
    {
        $deleteResource($resource);

        return redirect()->route('dashboard');
    }
}
