<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Core package
    |--------------------------------------------------------------------------
    |
    | The Composer package that holds the application core. The self-upgrade
    | feature reads its installed version and rewrites this requirement in the
    | skeleton's composer.json when upgrading.
    |
    */

    'package' => 'xbackbone/core',

    /*
    |--------------------------------------------------------------------------
    | Version metadata source
    |--------------------------------------------------------------------------
    |
    | Packagist metadata endpoint used to discover the latest stable release.
    | The lookup is cached for "cache_ttl" seconds; an explicit "Check now"
    | bypasses the cache.
    |
    */

    'packagist_url' => 'https://repo.packagist.org/p2/xbackbone/core.json',

    'cache_ttl' => 60 * 60 * 6,

    /*
    |--------------------------------------------------------------------------
    | Composer binary
    |--------------------------------------------------------------------------
    |
    | Optional path to the Composer binary used during an upgrade. When null,
    | Laravel's Composer helper auto-detects a local composer.phar or a global
    | "composer" command.
    |
    */

    'composer_binary' => env('XBB_COMPOSER_BINARY'),

];
