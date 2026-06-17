<?php

namespace App\Actions;

use App\Actions\Resource\DeleteResource;
use App\Models\Resource;
use App\Models\User;

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
    public function __invoke(User $user): void
    {
        $user->resources()->each(fn (Resource $resource) => ($this->deleteResource)($resource));

        $user->tokens()->delete();

        $user->delete();
    }
}
