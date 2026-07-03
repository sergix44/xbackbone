<?php

namespace XBB\Exceptions;

use XBB\Support\Helpers;
use RuntimeException;

class QuotaExceededException extends RuntimeException
{
    public function __construct(
        public readonly int $quota,
        public readonly int $used,
        public readonly int $incoming,
    ) {
        parent::__construct(__('Storage quota exceeded. :used of :quota used.', [
            'used' => Helpers::humanizeBytes($used),
            'quota' => Helpers::humanizeBytes($quota),
        ]));
    }
}
