<?php

use Illuminate\Foundation\Application;

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
    putenv('APP_ROOT='.APP_ROOT);
}

if (!defined('CORE_PACKAGE')) {
    define('CORE_PACKAGE', 'xbackbone/core');
}

putenv('COMPOSER_VENDOR_DIR='.APP_ROOT.'/vendor');

/** @var Application $app */
$app = require APP_ROOT.'/vendor/'.CORE_PACKAGE.'/bootstrap/app.php';

return $app->usePublicPath(APP_ROOT.'/public')
    ->useEnvironmentPath(APP_ROOT)
    ->useStoragePath(APP_ROOT.'/storage')
    ->useBootstrapPath(__DIR__);
