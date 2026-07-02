<?php

namespace App\Events\User;

use App\Models\User;

class UserPasswordChanged
{
    public function __construct(
        public readonly User $user,
        public readonly ?User $causer,
    ) {}
}
