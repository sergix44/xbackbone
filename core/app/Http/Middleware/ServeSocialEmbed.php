<?php

namespace XBB\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use XBB\Models\Resource;
use XBB\Support\SocialEmbed;

/**
 * Fast-path for social/link-preview crawlers (Discord, Telegram, Slack, ...).
 * When a known unfurler bot requests a resource preview page, it is served a
 * tiny, dependency-free HTML document containing only the OpenGraph/Twitter
 * meta tags instead of the full Livewire UI. Human visitors fall through to the
 * normal preview page, which emits the same tags via a `head` stack.
 *
 * Runs after {@see EnsureResourceAccessible} but re-checks access itself, so a
 * private, expired or locked resource can never leak its metadata to a bot
 * regardless of middleware ordering.
 */
class ServeSocialEmbed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->route()?->getName(), ['preview', 'preview.ext'], true)) {
            return $next($request);
        }

        if (! self::isUnfurler($request->userAgent())) {
            return $next($request);
        }

        $resource = $request->route('resource');

        if (! $resource instanceof Resource || ! $resource->isAccessibleBy($request->user())) {
            abort(404);
        }

        $locked = $resource->isLockedFor($request->user(), $request->session());

        // Short-circuiting the pipeline here means Preview::mount() never runs,
        // so a crawler fetch never increments the resource's view counter.
        return response()->view('embed.show', [
            'resource' => $resource,
            'embed' => SocialEmbed::forResource($resource, $locked),
        ]);
    }

    /**
     * Whether the given User-Agent belongs to a known link-preview crawler.
     */
    public static function isUnfurler(?string $userAgent): bool
    {
        if ($userAgent === null || $userAgent === '') {
            return false;
        }

        $userAgent = strtolower($userAgent);

        foreach ((array) config('embed.bots') as $needle) {
            if (str_contains($userAgent, (string) $needle)) {
                return true;
            }
        }

        return false;
    }
}
