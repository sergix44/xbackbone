<?php

namespace XBB\Events\Token;

use XBB\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class TokenCreated
{
    public function __construct(
        public readonly PersonalAccessToken $token,
        public readonly ?User $causer,
    ) {}
}
