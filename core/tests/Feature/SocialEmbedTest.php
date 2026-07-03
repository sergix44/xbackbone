<?php

use XBB\Http\Middleware\ServeSocialEmbed;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Resource;
use XBB\Support\SocialEmbed;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| User-Agent detection
|--------------------------------------------------------------------------
*/

test('detects known link-preview crawler user agents', function (string $userAgent) {
    expect(ServeSocialEmbed::isUnfurler($userAgent))->toBeTrue();
})->with([
    'Mozilla/5.0 (compatible; Discordbot/2.0; +https://discordapp.com)',
    'TelegramBot (like TwitterBot)',
    'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'Mozilla/5.0 (compatible; Slackbot-LinkExpanding 1.0; +https://api.slack.com/robots)',
    'WhatsApp/2.23.20.0 A',
    'Twitterbot/1.0',
    'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
]);

test('ignores regular browsers and empty user agents', function (?string $userAgent) {
    expect(ServeSocialEmbed::isUnfurler($userAgent))->toBeFalse();
})->with([
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
    '',
    null,
]);

/*
|--------------------------------------------------------------------------
| SocialEmbed builder — per type matrix
|--------------------------------------------------------------------------
*/

test('builds a large-image card for an image with a generated preview', function () {
    $resource = Resource::factory()->image()->withPreview()->create();

    $embed = SocialEmbed::forResource($resource, locked: false);

    expect($embed['ogType'])->toBe('website')
        ->and($embed['twitterCard'])->toBe('summary_large_image')
        ->and($embed['image'])->toContain('thumbnail')
        ->and($embed['oembedUrl'])->not->toBeNull();
});

test('falls back to the original image when no preview has been generated', function () {
    $resource = Resource::factory()->image()->create();

    expect(SocialEmbed::forResource($resource, false)['image'])->toBe($resource->raw_url);
});

test('still uses the original image while its preview is pending', function () {
    $resource = Resource::factory()->image()->pending()->create();

    expect(SocialEmbed::forResource($resource, false)['image'])->toBe($resource->raw_url);
});

test('never embeds a vector image as the card image', function () {
    $resource = Resource::factory()->svg()->create();

    expect(SocialEmbed::forResource($resource, false)['image'])->toBeNull();
});

test('builds a player card for a video', function () {
    $resource = Resource::factory()->video()->create();

    $embed = SocialEmbed::forResource($resource, false);

    expect($embed['ogType'])->toBe('video.other')
        ->and($embed['twitterCard'])->toBe('player')
        ->and($embed['video'])->toBe($resource->raw_url)
        ->and($embed['videoType'])->toBe($resource->mime)
        ->and($embed['videoWidth'])->toBe(720)
        ->and($embed['image'])->toBeNull(); // no poster without a generated preview
});

test('adds a poster to the video card once a preview exists', function () {
    $resource = Resource::factory()->video()->withPreview()->create();

    expect(SocialEmbed::forResource($resource, false)['image'])->toContain('thumbnail');
});

test('builds an audio card', function () {
    $resource = Resource::factory()->create([
        'type' => ResourceType::AUDIO,
        'mime' => 'audio/mpeg',
        'extension' => 'mp3',
        'filename' => 'song.mp3',
    ]);

    $embed = SocialEmbed::forResource($resource, false);

    expect($embed['ogType'])->toBe('music.song')
        ->and($embed['audio'])->toBe($resource->raw_url)
        ->and($embed['audioType'])->toBe('audio/mpeg');
});

test('builds an article card for textual resources', function () {
    $resource = Resource::factory()->text()->create();

    expect(SocialEmbed::forResource($resource, false)['ogType'])->toBe('article');
});

test('builds a link card described by its destination host', function () {
    $resource = Resource::factory()->link()->create(['data' => 'https://example.com/foo/bar']);

    $embed = SocialEmbed::forResource($resource, false);

    expect($embed['ogType'])->toBe('website')
        ->and($embed['description'])->toBe('example.com')
        ->and($embed['image'])->toBeNull()
        ->and($embed['oembedUrl'])->not->toBeNull();
});

test('builds a generic, non-leaking card for a locked resource', function () {
    $resource = Resource::factory()->image()->passwordProtected()->create();

    $embed = SocialEmbed::forResource($resource, locked: true);

    expect($embed['title'])->toBe('Protected file')
        ->and($embed['title'])->not->toContain($resource->filename)
        ->and($embed['image'])->toBeNull()
        ->and($embed['video'])->toBeNull()
        ->and($embed['oembedUrl'])->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Bot fast-path (the lightweight embed page)
|--------------------------------------------------------------------------
*/

test('a crawler is served the lightweight meta-only embed page', function () {
    $resource = Resource::factory()->image()->create();

    $this->withHeaders(['User-Agent' => 'Discordbot/2.0'])
        ->get(route('preview', ['resource' => $resource->code]))
        ->assertOk()
        ->assertSee('property="og:image"', false)
        ->assertSee('name="twitter:card"', false)
        ->assertSee($resource->raw_url, false)
        ->assertDontSee('csrf-token', false); // not the full Livewire layout
});

test('the normal preview page also carries the embed tags for unlisted clients', function () {
    $resource = Resource::factory()->image()->create();

    $this->get(route('preview', ['resource' => $resource->code]))
        ->assertOk()
        ->assertSee('csrf-token', false) // the full layout (human path)
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:image"', false);
});

test('a crawler fetch is not counted as a view', function () {
    $resource = Resource::factory()->image()->create();

    $this->withHeaders(['User-Agent' => 'Discordbot/2.0'])
        ->get(route('preview', ['resource' => $resource->code]))
        ->assertOk();

    expect($resource->fresh()->views)->toBe(0);
});

test('a crawler gets a 404 for a private resource', function () {
    $resource = Resource::factory()->image()->create(['is_private' => true]);

    $this->withHeaders(['User-Agent' => 'Discordbot/2.0'])
        ->get(route('preview', ['resource' => $resource->code]))
        ->assertNotFound();
});

test('a crawler gets a 404 for an expired resource', function () {
    $resource = Resource::factory()->image()->expired()->create();

    $this->withHeaders(['User-Agent' => 'Discordbot/2.0'])
        ->get(route('preview', ['resource' => $resource->code]))
        ->assertNotFound();
});

test('a crawler gets a generic card for a locked resource, leaking nothing', function () {
    $resource = Resource::factory()->image()->passwordProtected()->create();

    $this->withHeaders(['User-Agent' => 'Discordbot/2.0'])
        ->get(route('preview', ['resource' => $resource->code]))
        ->assertOk()
        ->assertSee('Protected file')
        ->assertDontSee($resource->filename)
        ->assertDontSee($resource->raw_url, false)
        ->assertDontSee('property="og:image"', false);
});

test('a crawler on a non-preview route is not served the embed page', function () {
    Storage::fake();
    $resource = Resource::factory()->image()->create();
    Storage::put($resource->storage_path, 'imagedata');

    $this->withHeaders(['User-Agent' => 'Discordbot/2.0'])
        ->get(route('raw', ['resource' => $resource->code]))
        ->assertOk()
        ->assertDontSee('property="og:title"', false);
});

/*
|--------------------------------------------------------------------------
| oEmbed endpoint
|--------------------------------------------------------------------------
*/

test('oembed returns a photo payload for an image', function () {
    $resource = Resource::factory()->image()->create();

    $this->getJson(route('oembed', ['url' => $resource->preview_ext_url]))
        ->assertOk()
        ->assertJson([
            'version' => '1.0',
            'type' => 'photo',
            'url' => $resource->raw_url,
            'width' => 720,
            'height' => 480,
        ]);
});

test('oembed returns a video payload with embeddable html', function () {
    $resource = Resource::factory()->video()->create();

    $this->getJson(route('oembed', ['url' => $resource->preview_ext_url]))
        ->assertOk()
        ->assertJson(['type' => 'video', 'width' => 720, 'height' => 480])
        ->assertJsonStructure(['version', 'type', 'html', 'width', 'height']);
});

test('oembed returns a link payload for a shortened link', function () {
    $resource = Resource::factory()->link()->create();

    $this->getJson(route('oembed', ['url' => $resource->preview_ext_url]))
        ->assertOk()
        ->assertJson(['type' => 'link']);
});

test('oembed denies a private resource', function () {
    $resource = Resource::factory()->image()->create(['is_private' => true]);

    $this->getJson(route('oembed', ['url' => $resource->preview_ext_url]))
        ->assertNotFound();
});

test('oembed denies a locked resource', function () {
    $resource = Resource::factory()->image()->passwordProtected()->create();

    $this->getJson(route('oembed', ['url' => $resource->preview_ext_url]))
        ->assertNotFound();
});

test('oembed returns 404 for an unknown url', function () {
    $this->getJson(route('oembed', ['url' => 'https://example.com/does-not-exist']))
        ->assertNotFound();
});
