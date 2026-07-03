<?php

namespace XBB\Features;

class PublicApiDocs
{
    public string $name = 'public-api-docs';

    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): mixed
    {
        return false;
    }
}
