<?php

namespace App\Listeners;

use App\Events\Resource\ResourceDeleted;
use App\Events\Resource\ResourceSettingsUpdated;
use App\Events\Resource\ResourceUploaded;
use App\Events\Resource\ResourceVisibilityToggled;

class LogResourceActivity
{
    public function handleResourceUploaded(ResourceUploaded $event): void
    {
        activity()->performedOn($event->resource)->causedBy($event->causer)->log('resource.uploaded');
    }

    public function handleResourceVisibilityToggled(ResourceVisibilityToggled $event): void
    {
        activity()->performedOn($event->resource)->causedBy($event->causer)
            ->log($event->resource->is_private ? 'resource.hidden' : 'resource.published');
    }

    public function handleResourceSettingsUpdated(ResourceSettingsUpdated $event): void
    {
        activity()->performedOn($event->resource)->causedBy($event->causer)->log('resource.updated');
    }

    public function handleResourceDeleted(ResourceDeleted $event): void
    {
        activity()->performedOn($event->resource)->causedBy($event->causer)->log('resource.deleted');
    }
}
