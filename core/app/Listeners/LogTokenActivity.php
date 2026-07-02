<?php

namespace App\Listeners;

use App\Events\Token\TokenCreated;
use App\Events\Token\TokenRevoked;

class LogTokenActivity
{
    public function handleTokenCreated(TokenCreated $event): void
    {
        activity()->performedOn($event->token)->causedBy($event->causer)->log('token.created');
    }

    public function handleTokenRevoked(TokenRevoked $event): void
    {
        activity()->performedOn($event->token)->causedBy($event->causer)->log('token.revoked');
    }
}
