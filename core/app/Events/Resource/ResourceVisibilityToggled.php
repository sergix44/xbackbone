<?php

namespace App\Events\Resource;

use App\Models\Resource;
use App\Models\User;

class ResourceVisibilityToggled
{
    public function __construct(
        public readonly Resource $resource,
        public readonly ?User $causer,
    ) {}
}
