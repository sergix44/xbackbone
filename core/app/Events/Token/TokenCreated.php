<?php

namespace XBB\Events\Token;

use Laravel\Sanctum\PersonalAccessToken;
use XBB\Models\User;

class TokenCreated
{
    public function __construct(
        public readonly PersonalAccessToken $token,
        public readonly ?User $causer,
    ) {}
}
