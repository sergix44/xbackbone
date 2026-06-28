<?php

namespace App\Providers;

use Mary\MaryServiceProvider;

class MaryBootServiceProvider extends MaryServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->registerComponents();
        $this->registerBladeDirectives();
    }
}
