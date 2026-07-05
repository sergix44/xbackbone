<?php

namespace XBB\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class LogAuthActivity
{
    public function handleLogin(Login $event): void
    {
        activity()->causedBy($this->toModel($event->user))->event('auth.login')->log('auth.login');
    }

    public function handleLogout(Logout $event): void
    {
        activity()->causedBy($this->toModel($event->user))->event('auth.logout')->log('auth.logout');
    }

    public function handleRegistered(Registered $event): void
    {
        $user = $this->toModel($event->user);

        if ($user === null) {
            return;
        }

        activity()->performedOn($user)->causedBy($user)->event('auth.registered')->log('auth.registered');
    }

    public function handleLockout(Lockout $event): void
    {
        activity()
            ->withProperties(['email' => $event->request->input('email')])
            ->event('auth.lockout')
            ->log('auth.lockout');
    }

    public function handleFailed(Failed $event): void
    {
        // $event->credentials contains the plaintext password (#[SensitiveParameter]);
        // never persist it. Only the identifying field is safe to store.
        activity()
            ->causedBy($this->toModel($event->user))
            ->withProperties(['email' => $event->credentials['email'] ?? null])
            ->event('auth.failed')
            ->log('auth.failed');
    }

    /**
     * `Login`/`Logout`/`Failed`/`Registered` type their `$user` property as the
     * `Authenticatable` interface, not `Model`, so it must be narrowed before use
     * with Spatie's `Model`-typed `causedBy()`/`performedOn()`. In this app the
     * `web` guard's provider is always `XBB\Models\User` (an Eloquent `Model`).
     */
    private function toModel(?Authenticatable $user): ?Model
    {
        return $user instanceof Model ? $user : null;
    }
}
