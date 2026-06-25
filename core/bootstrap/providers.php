<?php

use App\Installer\InstallerServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MaryBootServiceProvider;

return [
    InstallerServiceProvider::class,
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    MaryBootServiceProvider::class,
];
