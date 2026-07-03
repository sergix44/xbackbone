<?php

namespace XBB\Listeners;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passkeys\Events\PasskeyDeleted;
use Laravel\Passkeys\Events\PasskeyRegistered;

class LogPasskeyActivity
{
    public function handlePasskeyRegistered(PasskeyRegistered $event): void
    {
        activity()->performedOn($event->passkey)->causedBy($this->toModel($event->user))->log('passkey.added');
    }

    public function handlePasskeyDeleted(PasskeyDeleted $event): void
    {
        activity()->performedOn($event->passkey)->causedBy($this->toModel($event->user))->log('passkey.removed');
    }

    /**
     * `PasskeyRegistered`/`PasskeyDeleted` type their `$user` property as the
     * `Authenticatable` interface, not `Model`, so it must be narrowed before
     * use with Spatie's `Model`-typed `causedBy()`.
     */
    private function toModel(Authenticatable $user): ?Model
    {
        return $user instanceof Model ? $user : null;
    }
}
