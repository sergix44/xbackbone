<?php

namespace XBB\Actions\Token;

use Laravel\Sanctum\PersonalAccessToken;
use XBB\Events\Token\TokenRevoked;
use XBB\Models\User;

class RevokeToken
{
    public function __invoke(PersonalAccessToken $token, ?User $causer = null): void
    {
        $token->delete();

        event(new TokenRevoked($token, $causer));
    }
}
