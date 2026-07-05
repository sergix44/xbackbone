<?php

namespace XBB\Listeners;

use XBB\Events\User\UserCreated;
use XBB\Events\User\UserDeleted;
use XBB\Events\User\UserPasswordChanged;
use XBB\Events\User\UserProfileUpdated;
use XBB\Events\User\UserUpdated;

class LogUserActivity
{
    public function handleUserCreated(UserCreated $event): void
    {
        activity()->performedOn($event->user)->causedBy($event->causer)
            ->event('user.created')->log('user.created');
    }

    public function handleUserUpdated(UserUpdated $event): void
    {
        activity()->performedOn($event->user)->causedBy($event->causer)
            ->event('user.updated')->log('user.updated');
    }

    public function handleUserDeleted(UserDeleted $event): void
    {
        activity()->performedOn($event->user)->causedBy($event->causer)
            ->event('user.deleted')->log('user.deleted');
    }

    public function handleUserPasswordChanged(UserPasswordChanged $event): void
    {
        activity()->performedOn($event->user)->causedBy($event->causer)
            ->event('user.password_changed')->log('user.password_changed');
    }

    public function handleUserProfileUpdated(UserProfileUpdated $event): void
    {
        activity()->performedOn($event->user)->causedBy($event->causer)
            ->event('user.profile_updated')->log('user.profile_updated');
    }
}
