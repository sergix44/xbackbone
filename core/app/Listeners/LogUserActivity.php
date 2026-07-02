<?php

namespace App\Listeners;

use App\Events\User\UserCreated;
use App\Events\User\UserDeleted;
use App\Events\User\UserPasswordChanged;
use App\Events\User\UserProfileUpdated;
use App\Events\User\UserUpdated;

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
