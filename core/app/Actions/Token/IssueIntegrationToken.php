<?php

namespace XBB\Actions\Token;

use XBB\Events\Token\TokenCreated;
use XBB\Models\User;
use Laravel\Sanctum\NewAccessToken;

class IssueIntegrationToken
{
    /**
     * Issue a personal access token for a user, e.g. when they download an
     * integration config or installer that bakes in a fresh Sanctum token.
     * Every issuance dispatches a {@see TokenCreated} event from this single
     * point, regardless of which client (ShareX, ishare, CLI, KDE, macOS,
     * ScreenCloud, ...) requested it.
     *
     * @param  array<int, string>  $abilities
     */
    public function __invoke(User $user, string $name, array $abilities): NewAccessToken
    {
        $token = $user->createToken($name, $abilities);

        event(new TokenCreated($token->accessToken, $user));

        return $token;
    }
}
