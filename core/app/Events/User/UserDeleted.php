<?php

namespace App\Events\User;

use App\Models\User;

class UserDeleted
{
    public function __construct(
        public readonly User $user,
        public readonly ?User $causer,
    ) {}
}
