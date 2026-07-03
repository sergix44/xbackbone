<?php

namespace XBB\Events\Resource;

use XBB\Models\Resource;
use XBB\Models\User;

class ResourceDeleted
{
    public function __construct(
        public readonly Resource $resource,
        public readonly ?User $causer,
    ) {}
}
