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
        activity()->performedOn($event->resource)->causedBy($event->causer)
            ->event('resource.uploaded')->log('resource.uploaded');
    }

    public function handleResourceVisibilityToggled(ResourceVisibilityToggled $event): void
    {
        $eventName = $event->resource->is_private ? 'resource.hidden' : 'resource.published';

        activity()->performedOn($event->resource)->causedBy($event->causer)
            ->event($eventName)->log($eventName);
    }

    public function handleResourceSettingsUpdated(ResourceSettingsUpdated $event): void
    {
        activity()->performedOn($event->resource)->causedBy($event->causer)
            ->event('resource.updated')->log('resource.updated');
    }

    public function handleResourceDeleted(ResourceDeleted $event): void
    {
        activity()->performedOn($event->resource)->causedBy($event->causer)
            ->event('resource.deleted')->log('resource.deleted');
    }
}
