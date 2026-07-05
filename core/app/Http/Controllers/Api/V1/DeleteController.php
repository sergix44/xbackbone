<?php

namespace XBB\Http\Controllers\Api\V1;

use Illuminate\Http\Response;
use XBB\Actions\Resource\DeleteResource;
use XBB\Http\Controllers\Controller;
use XBB\Models\Resource;

class DeleteController extends Controller
{
    public function __invoke(Resource $resource, DeleteResource $deleteResource): Response
    {
        abort_unless(
            $resource->user_id === auth()->id() || auth()->user()->can('administrate'),
            403
        );

        $deleteResource($resource, auth()->user());

        return response()->noContent();
    }
}
