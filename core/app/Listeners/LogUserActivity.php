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
        activity()->performedOn($event->user)->causedBy($event->causer)->log('user.created');
    }

    public function handleUserUpdated(UserUpdated $event): void
    {
        activity()->performedOn($event->user)->causedBy($event->causer)->log('user.updated');
    }

    public function handleUserDeleted(UserDeleted $event): void
    {
        activity()->performedOn($event->user)->causedBy($event->causer)->log('user.deleted');
    }

    public function handleUserPasswordChanged(UserPasswordChanged $event): void
    {
        activity()->performedOn($event->user)->causedBy($event->causer)->log('user.password_changed');
    }

    public function handleUserProfileUpdated(UserProfileUpdated $event): void
    {
        activity()->performedOn($event->user)->causedBy($event->causer)->log('user.profile_updated');
    }
}
