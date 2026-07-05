<?php

namespace XBB\Events\Token;

use Laravel\Sanctum\PersonalAccessToken;
use XBB\Models\User;

class TokenRevoked
{
    public function __construct(
        public readonly PersonalAccessToken $token,
        public readonly ?User $causer,
    ) {}
}
