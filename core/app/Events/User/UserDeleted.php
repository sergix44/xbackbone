<?php

namespace XBB\Events\User;

use XBB\Models\User;

class UserDeleted
{
    public function __construct(
        public readonly User $user,
        public readonly ?User $causer,
    ) {}
}
