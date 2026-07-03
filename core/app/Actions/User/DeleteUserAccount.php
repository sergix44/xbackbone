<?php

namespace XBB\Actions\User;

use XBB\Actions\Resource\DeleteResource;
use XBB\Events\User\UserDeleted;
use XBB\Models\Resource;
use XBB\Models\User;

class DeleteUserAccount
{
    public function __construct(private DeleteResource $deleteResource) {}

    /**
     * Permanently delete a user together with everything they own.
     *
     * Resources are removed through {@see DeleteResource}, which deletes the
     * physical file only when no other resource (of any user) still references
     * the same content-addressed fingerprint, so files shared with other users
     * are preserved. API tokens are revoked before the user row is removed.
     */
    public function __invoke(User $user, ?User $causer = null): void
    {
        $user->resources()->each(fn (Resource $resource) => ($this->deleteResource)($resource, $causer));

        $user->tokens()->delete();

        $user->delete();

        event(new UserDeleted($user, $causer));
    }
}
