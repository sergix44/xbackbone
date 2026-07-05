<?php

namespace XBB\Listeners;

use XBB\Events\Token\TokenCreated;
use XBB\Events\Token\TokenRevoked;

class LogTokenActivity
{
    public function handleTokenCreated(TokenCreated $event): void
    {
        activity()->performedOn($event->token)->causedBy($event->causer)
            ->event('token.created')->log('token.created');
    }

    public function handleTokenRevoked(TokenRevoked $event): void
    {
        activity()->performedOn($event->token)->causedBy($event->causer)
            ->event('token.revoked')->log('token.revoked');
    }
}
