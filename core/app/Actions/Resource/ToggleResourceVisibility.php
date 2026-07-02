<?php

namespace App\Actions\Resource;

use App\Events\Resource\ResourceVisibilityToggled;
use App\Models\Resource;

class ToggleResourceVisibility
{
    public function __invoke(Resource $resource): Resource
    {
        $resource->update([
            'is_private' => ! $resource->is_private,
        ]);

        event(new ResourceVisibilityToggled($resource, $resource->user));

        return $resource;
    }
}
