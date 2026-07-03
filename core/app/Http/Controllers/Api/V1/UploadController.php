<?php

namespace XBB\Http\Controllers\Api\V1;

use XBB\Actions\Resource\StoreResource;
use XBB\Exceptions\QuotaExceededException;
use XBB\Http\Controllers\Controller;
use XBB\Http\Requests\Api\V1\UploadResourceRequest;
use XBB\Http\Resources\Api\V1\ResourceResource;

class UploadController extends Controller
{
    public function __invoke(UploadResourceRequest $request, StoreResource $uploadResource)
    {
        try {
            $resource = $uploadResource(
                auth()->user(),
                $request->file('file'),
                $request->input('name'),
                $request->input('data')
            );
        } catch (QuotaExceededException $e) {
            abort(413, $e->getMessage());
        }

        return new ResourceResource($resource);
    }
}
