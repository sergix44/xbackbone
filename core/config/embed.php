<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenGraph image bounds
    |--------------------------------------------------------------------------
    |
    | Bounding box (in pixels) for the thumbnail linked as `og:image`. A bounded
    | preview is served instead of the original file so unfurlers stay within
    | per-platform size limits (Twitter ~5MB, Discord ~8MB, WhatsApp <600KB).
    | `og:image` must resolve to an absolute, publicly reachable URL, so make
    | sure APP_URL is correct in production.
    |
    */

    'image' => [
        'width' => (int) env('EMBED_IMAGE_WIDTH', 1200),
        'height' => (int) env('EMBED_IMAGE_HEIGHT', 630),
    ],

    /*
    |--------------------------------------------------------------------------
    | oEmbed player size
    |--------------------------------------------------------------------------
    |
    | Default dimensions reported by the oEmbed endpoint and reused for the video
    | player box in the OpenGraph/Twitter tags (we do not store real media
    | dimensions, so these act as sensible defaults).
    |
    */

    'oembed' => [
        'width' => (int) env('EMBED_OEMBED_WIDTH', 720),
        'height' => (int) env('EMBED_OEMBED_HEIGHT', 480),
    ],

    /*
    |--------------------------------------------------------------------------
    | Link-preview crawler user agents
    |--------------------------------------------------------------------------
    |
    | Lowercase substrings matched case-insensitively against the request
    | User-Agent. A match serves the lightweight meta-only embed page instead of
    | the full Livewire UI. This is only an optimisation: the same tags are also
    | emitted on the normal preview page, so unlisted crawlers still get them.
    |
    */

    'bots' => [
        'facebookexternalhit',
        'facebookcatalog',
        'meta-externalagent',
        'facebot',
        'twitterbot',
        'discordbot',
        'telegrambot',
        'slackbot',
        'slack-imgproxy',
        'whatsapp',
        'linkedinbot',
        'pinterest',
        'redditbot',
        'applebot',
        'googlebot',
        'google-inspectiontool',
        'bingbot',
        'skypeuripreview',
        'embedly',
        'iframely',
        'vkshare',
        'qwantify',
        'duckduckbot',
        'flipboardproxy',
        'tumblr',
        'mastodon',
        'w3c_validator',
        'bitlybot',
        'nuzzel',
        'outbrain',
        'quora link preview',
        'showyoubot',
    ],

];
