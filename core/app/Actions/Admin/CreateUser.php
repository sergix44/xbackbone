<?php

namespace XBB\Actions\Admin;

use XBB\Events\User\UserCreated;
use XBB\Models\Properties\UserStatus;
use XBB\Models\User;

class CreateUser
{
    /**
     * Create a user from the admin panel.
     *
     * Attributes are assigned explicitly rather than through mass assignment:
     * {@see User::$fillable} is intentionally limited to name/email/password to
     * prevent privilege escalation during self-registration, so the admin-only
     * fields (is_admin, status, quota) are set directly here. The plain password
     * is hashed by the model's `hashed` cast. Admin-created accounts are marked
     * as verified so the user can sign in immediately.
     *
     * @param  array{name: string, email: string, password: string, is_admin: bool, status: UserStatus, quota: int}  $attributes
     */
    public function __invoke(array $attributes, ?User $causer = null): User
    {
        $user = new User;

        $user->name = $attributes['name'];
        $user->email = $attributes['email'];
        $user->password = $attributes['password'];
        $user->is_admin = $attributes['is_admin'];
        $user->status = $attributes['status'];
        $user->quota = $attributes['quota'];
        $user->email_verified_at = now();

        $user->save();

        event(new UserCreated($user, $causer));

        return $user;
    }
}
