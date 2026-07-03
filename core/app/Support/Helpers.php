<?php

namespace XBB\Support;

use XBB\Models\User;
use Laravel\Pennant\Feature;

class Helpers
{
    /**
     * Resolve the theme to apply for the given user.
     *
     * A user's personal theme wins, but an empty value (guests, or users who
     * never picked one) falls back to the global default theme. Empty string
     * and null are treated alike so the global default is never shadowed.
     */
    public static function theme(?User $user = null): string
    {
        return (string) ($user?->theme ?: Feature::value('default-theme'));
    }

    public static function humanizeBytes(int $bytes, int $decimals = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = (int) floor((strlen((string) $bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).' '.$units[$factor];
    }

    /**
     * Convert a PHP ini shorthand byte value (e.g. "2M", "8K", "1G") to bytes.
     */
    public static function iniSizeToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $number = (int) $value;

        return match (strtolower($value[strlen($value) - 1])) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
