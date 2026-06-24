<?php

namespace App\Actions\Resource;

use App\Models\Resource;
use Illuminate\Support\Carbon;

class UpdateResourceSettings
{
    /**
     * Update a resource's sharing settings. The expiration is always applied when
     * its key is present (a null/empty value clears it). The password is set when
     * a non-empty value is given, cleared when `remove_password` is true, and left
     * untouched otherwise — the stored hash can never be read back to "keep" it.
     *
     * @param array{
     *     expires_at?: \DateTimeInterface|string|null,
     *     password?: ?string,
     *     remove_password?: bool
     * } $attributes
     */
    public function __invoke(Resource $resource, array $attributes): Resource
    {
        if (array_key_exists('expires_at', $attributes)) {
            $expiresAt = $attributes['expires_at'] ?: null;
            $resource->expires_at = $expiresAt === null ? null : Carbon::parse($expiresAt);
        }

        if (! empty($attributes['remove_password'])) {
            $resource->password = null;
        } elseif (! empty($attributes['password'])) {
            $resource->password = $attributes['password'];
        }

        $resource->save();

        return $resource;
    }
}
