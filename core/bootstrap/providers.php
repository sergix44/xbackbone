<?php

use XBB\Installer\InstallerServiceProvider;
use XBB\Providers\AppServiceProvider;
use XBB\Providers\FortifyServiceProvider;
use XBB\Providers\MaryBootServiceProvider;

return [
    InstallerServiceProvider::class,
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    MaryBootServiceProvider::class,
];
