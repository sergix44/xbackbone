<?php

namespace XBB\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Symfony\Component\HttpFoundation\Response;
use XBB\Installer\Support\InstallationState;

/**
 * Redirects every web request to the installer until setup is complete.
 *
 * Prepended to the `web` group so it runs before route-model binding — an
 * uninstalled instance has no database, so binding must never be attempted.
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallationState::isInstalled() || $this->isExempt($request)) {
            return $next($request);
        }

        return redirect()->route('installer.index');
    }

    /**
     * Paths that must remain reachable while the app is not yet installed.
     */
    private function isExempt(Request $request): bool
    {
        if ($request->routeIs('installer.*')) {
            return true;
        }

        if ($request->is('up')) {
            return true;
        }

        // The wizard is a Livewire component; its AJAX round-trips hit the
        // APP_KEY-derived endpoint prefix, which must not be intercepted.
        $livewirePrefix = ltrim(EndpointResolver::prefix(), '/');

        if ($livewirePrefix !== '' && $request->is($livewirePrefix.'/*')) {
            return true;
        }

        return $request->is('build/*', 'vendor/*', 'img/*', 'favicon.ico', 'storage/*', 'hot');
    }
}
