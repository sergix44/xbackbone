<?php

namespace XBB\Actions\Resource;

use XBB\Events\Resource\ResourceVisibilityToggled;
use XBB\Models\Resource;

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
