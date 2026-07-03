<?php

namespace XBB\Http\Middleware;

use XBB\Models\Resource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the public resource routes (preview, raw, download, thumbnail). Private
 * or expired resources are hidden from everyone but their owner/admin, and
 * password-protected resources require the visitor to have unlocked them in the
 * current session.
 */
class EnsureResourceAccessible
{
    public function handle(Request $request, Closure $next): Response
    {
        $resource = $request->route('resource');

        if (! $resource instanceof Resource) {
            abort(404);
        }

        $user = $request->user();

        // Private or expired resources do not exist publicly.
        if (! $resource->isAccessibleBy($user)) {
            abort(404);
        }

        if ($resource->isLockedFor($user, $request->session())) {
            $routeName = $request->route()?->getName();

            // Raw is the navigational entry point: bounce it to the preview to unlock.
            if (in_array($routeName, ['raw', 'raw.ext'], true)) {
                return redirect()->route('preview', ['resource' => $resource->code]);
            }

            // The preview page renders the unlock form itself, so let it through;
            // serving routes (download, thumbnail) are denied outright.
            if (! in_array($routeName, ['preview', 'preview.ext'], true)) {
                abort(403);
            }
        }

        return $next($request);
    }
}
