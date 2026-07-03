<?php

namespace XBB\Installer;

use XBB\Installer\Http\Middleware\EnsureInstalled;
use XBB\Installer\Http\Middleware\RedirectIfInstalled;
use XBB\Installer\Livewire\Installer;
use XBB\Installer\Support\InstallationState;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InstallerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Before installation there is no database, so swap every
        // database-backed default for a driverless equivalent. Skipped under
        // tests, which already provide working in-memory drivers.
        if (InstallationState::isInstalled() || $this->app->runningUnitTests()) {
            return;
        }

        config([
            'session.driver' => 'file',
            'cache.default' => 'file',
            'queue.default' => 'sync',
            'pennant.default' => 'array',
            'database.default' => 'sqlite',
        ]);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'installer');

        // The route is registered during boot(), before the framework loads
        // routes/web.php in a booted callback, so it takes precedence over the
        // single-segment {resource:code} catch-all.
        Route::middleware('web')->group(function (): void {
            Route::livewire('install', Installer::class)
                ->name('installer.index')
                ->middleware(RedirectIfInstalled::class);
        });

        // The guard checks the installation state per request, so it is safe to
        // attach unconditionally; it is a no-op once the app is installed.
        Route::prependMiddlewareToGroup('web', EnsureInstalled::class);

        if (! InstallationState::isInstalled() && ! $this->app->runningUnitTests()) {
            $this->ensureRuntimeDirectories();
        }
    }

    /**
     * Ensure the directories file-based sessions/cache/views need exist, since
     * a fresh skeleton may not have created them yet.
     */
    private function ensureRuntimeDirectories(): void
    {
        foreach (['framework/sessions', 'framework/cache', 'framework/views'] as $directory) {
            $path = storage_path($directory);

            if (! is_dir($path)) {
                @mkdir($path, 0775, true);
            }
        }
    }
}
