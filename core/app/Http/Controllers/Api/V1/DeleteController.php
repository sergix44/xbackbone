<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Resource\DeleteResource;
use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Response;

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
