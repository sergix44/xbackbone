<?php

namespace App\Events\Token;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class TokenRevoked
{
    public function __construct(
        public readonly PersonalAccessToken $token,
        public readonly ?User $causer,
    ) {}
}
