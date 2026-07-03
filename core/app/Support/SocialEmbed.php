<?php

namespace XBB\Support;

use XBB\Http\Middleware\ServeSocialEmbed;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;

/**
 * Builds the OpenGraph / Twitter-Card metadata that lets third-party clients
 * (Discord, Telegram, Slack, Facebook, X, ...) render rich previews of a shared
 * resource. It is the single source of truth consumed both by the bot fast-path
 * ({@see ServeSocialEmbed}) and by the human preview page.
 */
class SocialEmbed
{
    /**
     * Brand accent color, kept in sync with the `msapplication-TileColor` meta
     * in the base layout. Some clients (e.g. Discord) tint the card with it.
     */
    private const THEME_COLOR = '#603cba';

    /**
     * Build the embed metadata for a resource.
     *
     * When $locked is true (a password-protected resource the visitor has not
     * unlocked) a deliberately generic card is returned that leaks neither the
     * filename nor a thumbnail, guaranteeing no disclosure even if a caller
     * reaches this method without the access middleware having run.
     *
     * @return array<string, string|int|null>
     */
    public static function forResource(Resource $resource, bool $locked): array
    {
        if ($locked) {
            return self::make(
                $resource,
                title: __('Protected file'),
                description: __('A password-protected file on :app.', ['app' => config('app.name')]),
            );
        }

        return match ($resource->type) {
            ResourceType::IMAGE => self::make(
                $resource,
                title: $resource->display_name,
                description: self::describe($resource),
                twitterCard: 'summary_large_image',
                image: self::imageUrl($resource),
                oembedUrl: self::oembedUrl($resource),
            ),
            ResourceType::VIDEO => self::make(
                $resource,
                title: $resource->display_name,
                description: self::describe($resource),
                ogType: 'video.other',
                twitterCard: 'player',
                image: self::previewThumbnail($resource),
                video: $resource->raw_url,
                videoType: $resource->mime,
                videoWidth: (int) config('embed.oembed.width'),
                videoHeight: (int) config('embed.oembed.height'),
                oembedUrl: self::oembedUrl($resource),
            ),
            ResourceType::AUDIO => self::make(
                $resource,
                title: $resource->display_name,
                description: self::describe($resource),
                ogType: 'music.song',
                image: self::previewThumbnail($resource),
                audio: $resource->raw_url,
                audioType: $resource->mime,
            ),
            ResourceType::PDF => self::make(
                $resource,
                title: $resource->display_name,
                description: self::describe($resource),
                twitterCard: $resource->has_preview ? 'summary_large_image' : 'summary',
                image: self::previewThumbnail($resource),
            ),
            ResourceType::TEXT => self::make(
                $resource,
                title: $resource->display_name,
                description: self::describe($resource),
                ogType: 'article',
                image: self::previewThumbnail($resource),
            ),
            ResourceType::LINK => self::make(
                $resource,
                title: $resource->display_name,
                description: self::linkHost($resource),
                oembedUrl: self::oembedUrl($resource),
            ),
            default => self::make(
                $resource,
                title: $resource->display_name,
                description: self::describe($resource),
            ),
        };
    }

    /**
     * Assemble the full tag set, applying shared defaults so each resource type
     * only specifies what differs.
     *
     * @return array{
     *     title: string,
     *     description: ?string,
     *     url: string,
     *     siteName: string,
     *     ogType: string,
     *     twitterCard: string,
     *     themeColor: string,
     *     image: ?string,
     *     video: ?string,
     *     videoType: ?string,
     *     videoWidth: ?int,
     *     videoHeight: ?int,
     *     audio: ?string,
     *     audioType: ?string,
     *     oembedUrl: ?string,
     * }
     */
    private static function make(
        Resource $resource,
        string $title,
        ?string $description = null,
        string $ogType = 'website',
        string $twitterCard = 'summary',
        ?string $image = null,
        ?string $video = null,
        ?string $videoType = null,
        ?int $videoWidth = null,
        ?int $videoHeight = null,
        ?string $audio = null,
        ?string $audioType = null,
        ?string $oembedUrl = null,
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'url' => $resource->preview_url,
            'siteName' => (string) config('app.name'),
            'ogType' => $ogType,
            'twitterCard' => $twitterCard,
            'themeColor' => self::THEME_COLOR,
            'image' => $image,
            'video' => $video,
            'videoType' => $videoType,
            'videoWidth' => $videoWidth,
            'videoHeight' => $videoHeight,
            'audio' => $audio,
            'audioType' => $audioType,
            'oembedUrl' => $oembedUrl,
        ];
    }

    /**
     * The `og:image` source for an image resource: a bounded thumbnail when one
     * has been generated, otherwise the original raster served as-is. Vector
     * (SVG) images are skipped as most clients cannot embed them.
     */
    private static function imageUrl(Resource $resource): ?string
    {
        $thumbnail = self::previewThumbnail($resource);

        if ($thumbnail !== null) {
            return $thumbnail;
        }

        if ($resource->is_displayable && $resource->mime !== 'image/svg+xml') {
            return $resource->raw_url;
        }

        return null;
    }

    /**
     * The bounded preview thumbnail URL, or null when no preview exists yet.
     * Used as the card image for images, and as the poster for video/PDF.
     */
    private static function previewThumbnail(Resource $resource): ?string
    {
        if (! $resource->has_preview) {
            return null;
        }

        return route('thumbnail', [
            'resource' => $resource->code,
            'w' => (int) config('embed.image.width'),
            'h' => (int) config('embed.image.height'),
        ]);
    }

    private static function oembedUrl(Resource $resource): string
    {
        return route('oembed', ['url' => $resource->preview_ext_url]);
    }

    /**
     * A short description combining the human-readable size and the mime type.
     */
    private static function describe(Resource $resource): ?string
    {
        $parts = array_filter([
            $resource->size_human_readable,
            $resource->mime,
        ]);

        return $parts !== [] ? implode(' · ', $parts) : null;
    }

    /**
     * The destination host of a link resource, used as its card description.
     */
    private static function linkHost(Resource $resource): ?string
    {
        $host = parse_url((string) $resource->data, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : null;
    }
}
