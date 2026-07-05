<?php

namespace XBB\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;

/**
 * Serves oEmbed (https://oembed.com) responses for resource preview URLs, which
 * clients such as Slack, Mastodon and Embedly use to build rich previews. The
 * endpoint is discovered through the `application/json+oembed` link emitted in
 * the preview page head.
 */
class OembedController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $code = self::codeFromUrl((string) $request->query('url'));

        if ($code === null) {
            abort(404);
        }

        $resource = Resource::query()->where('code', $code)->first();

        // The oEmbed endpoint must honour the same access rules as the resource
        // itself: hidden, expired or still-locked resources are not described.
        if ($resource === null
            || ! $resource->isAccessibleBy($request->user())
            || $resource->isLockedFor($request->user(), $request->session())) {
            abort(404);
        }

        $width = (int) config('embed.oembed.width');
        $height = (int) config('embed.oembed.height');

        $payload = [
            'version' => '1.0',
            'provider_name' => (string) config('app.name'),
            'provider_url' => (string) config('app.url'),
            'title' => $resource->display_name,
            'author_name' => $resource->user->name,
        ];

        $payload += match ($resource->type) {
            ResourceType::IMAGE => [
                'type' => 'photo',
                'url' => $resource->raw_url,
                'width' => $width,
                'height' => $height,
            ],
            ResourceType::VIDEO => [
                'type' => 'video',
                'html' => sprintf(
                    '<iframe src="%s" width="%d" height="%d" frameborder="0" allowfullscreen></iframe>',
                    e($resource->raw_url),
                    $width,
                    $height,
                ),
                'width' => $width,
                'height' => $height,
            ],
            default => [
                'type' => 'link',
            ],
        };

        if ($resource->has_preview) {
            $payload['thumbnail_url'] = $resource->thumbnail_url;
        }

        return response()->json($payload);
    }

    /**
     * Extract a resource code from an oEmbed `url` parameter pointing at a
     * preview page (e.g. ".../abc123" or ".../abc123.png"). Returns null when
     * the URL does not look like one of our preview links.
     */
    private static function codeFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $code = pathinfo(basename($path), PATHINFO_FILENAME);

        return $code !== '' ? $code : null;
    }
}
