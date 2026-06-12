<?php

namespace App\Support;

class Helpers
{
    public static function humanizeBytes(int $bytes, int $decimals = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);

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
