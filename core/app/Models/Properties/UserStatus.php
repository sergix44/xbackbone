<?php

namespace App\Models\Properties;

enum UserStatus: int
{
    case ENABLED = 1;
    case DISABLED = 0;
    case API_ONLY = 2;
    case SSO_ONLY = 3;

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::ENABLED => 'Enabled',
            self::DISABLED => 'Disabled',
            self::API_ONLY => 'API only',
            self::SSO_ONLY => 'SSO only',
        };
    }
}
