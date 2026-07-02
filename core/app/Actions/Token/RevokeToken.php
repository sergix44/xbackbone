<?php

namespace App\Actions\Token;

use App\Events\Token\TokenRevoked;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class RevokeToken
{
    public function __invoke(PersonalAccessToken $token, ?User $causer = null): void
    {
        $token->delete();

        event(new TokenRevoked($token, $causer));
    }
}
