<?php

namespace App\Events\Resource;

use App\Models\Resource;
use App\Models\User;

class ResourceSettingsUpdated
{
    public function __construct(
        public readonly Resource $resource,
        public readonly ?User $causer,
    ) {}
}
