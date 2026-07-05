<?php

namespace XBB\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use XBB\Installer\Support\InstallationState;

/**
 * Guards the installer route so it cannot be reopened once setup is complete.
 */
class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallationState::isInstalled()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
