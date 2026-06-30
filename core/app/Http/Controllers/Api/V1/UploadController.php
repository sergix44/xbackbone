<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Resource\StoreResource;
use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UploadResourceRequest;
use App\Http\Resources\Api\V1\ResourceResource;

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
