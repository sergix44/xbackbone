<?php

namespace XBB\Features;

use Sqids\Sqids;

class AlphabetForIds
{
    public string $name = 'id-alphabet';

    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): mixed
    {
        return str_shuffle(Sqids::DEFAULT_ALPHABET);
    }
}
