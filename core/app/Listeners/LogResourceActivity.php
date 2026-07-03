<?php

namespace XBB\Listeners;

use XBB\Events\Resource\ResourceDeleted;
use XBB\Events\Resource\ResourceSettingsUpdated;
use XBB\Events\Resource\ResourceUploaded;
use XBB\Events\Resource\ResourceVisibilityToggled;

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
