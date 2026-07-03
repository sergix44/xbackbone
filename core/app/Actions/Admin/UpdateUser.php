<?php

namespace XBB\Actions\Admin;

use XBB\Events\User\UserUpdated;
use XBB\Models\Properties\UserStatus;
use XBB\Models\User;

class UpdateUser
{
    /**
     * Update a user from the admin panel.
     *
     * Like {@see CreateUser}, admin-only fields are assigned explicitly to bypass
     * the restricted {@see User::$fillable}. The password is only changed when a
     * new one is provided, otherwise the current one is kept.
     *
     * @param  array{name: string, email: string, password: ?string, is_admin: bool, status: UserStatus, quota: int}  $attributes
     */
    public function __invoke(User $user, array $attributes, ?User $causer = null): User
    {
        $user->name = $attributes['name'];
        $user->email = $attributes['email'];
        $user->is_admin = $attributes['is_admin'];
        $user->status = $attributes['status'];
        $user->quota = $attributes['quota'];

        if (! empty($attributes['password'])) {
            $user->password = $attributes['password'];
        }

        $user->save();

        event(new UserUpdated($user, $causer));

        return $user;
    }
}
