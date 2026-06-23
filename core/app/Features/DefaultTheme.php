<?php

namespace App\Features;

class DefaultTheme
{
    public string $name = 'default-theme';

    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): mixed
    {
        return '';
    }
}
