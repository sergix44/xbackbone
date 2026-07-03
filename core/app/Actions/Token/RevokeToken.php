<?php

namespace XBB\Actions\Token;

use XBB\Events\Token\TokenRevoked;
use XBB\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class RevokeToken
{
    public function __invoke(PersonalAccessToken $token, ?User $causer = null): void
    {
        $token->delete();

        event(new TokenRevoked($token, $causer));
    }
}
