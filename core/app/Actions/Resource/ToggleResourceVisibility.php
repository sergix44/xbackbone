<?php

namespace App\Actions\Resource;

use App\Models\Resource;

class ToggleResourceVisibility
{
    public function __invoke(Resource $resource): Resource
    {
        $resource->update([
            'is_private' => ! $resource->is_private,
        ]);

        return $resource;
    }
}
