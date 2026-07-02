<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

test('integrations page renders all available integrations', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('integrations'))
        ->assertOk()
        ->assertSee('Integrations')
        ->assertSee('ShareX')
        ->assertSee('Xerahs')
        ->assertSee('ScreenCloud')
        ->assertSee('ishare')
        ->assertSee('Spectacle')
        ->assertSee('macOS')
        ->assertSee('Capture apps')
        ->assertSee('CLI scripts')
        ->assertSee('portable shell uploader')
        ->assertSee('https://getsharex.com/')
        ->assertSee('https://xerahs.com')
        ->assertSee('https://screencloud.net')
        ->assertSee('https://isharemac.app/')
        ->assertSee('https://apps.kde.org/spectacle/')
        ->assertSee('Copy install link')
        ->assertSee('Download package')
        ->assertDontSee('Linux Desktop')
        ->assertDontSee('@js(');
});

test('integrations page requires authentication', function () {
    $this->get(route('integrations'))
        ->assertRedirect(route('login'));
});

test('downloads a working ShareX uploader config', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    $response = $this->actingAs($user)
        ->get(route('integrations.sharex'))
        ->assertOk()
        ->assertDownload('jane-doe-sharex.sxcu');

    $config = $response->json();

    expect($config['Version'])->toBe('17.0.0');
    expect($config['RequestMethod'])->toBe('POST');
    expect($config['RequestURL'])->toBe(route('api.v1.upload'));
    expect($config['Body'])->toBe('MultipartFormData');
    expect($config['FileFormName'])->toBe('file');
    expect($config['Headers']['Authorization'])->toStartWith('Bearer ');
    expect($config['URL'])->toBe('{json:data.preview_ext_url}');
    expect($config['ThumbnailURL'])->toBe('{json:data.raw_url}');
    expect($config['DeletionURL'])->toBe('{json:data.deletion_url}');
    expect($config['ErrorMessage'])->toBe('{json:message}');
    expect($config['DestinationType'])->toContain('URLShortener');
    expect($config['DestinationType'])->toContain('URLSharingService');
});

test('issues a ShareX token to the user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('integrations.sharex'))->assertOk();

    expect($user->tokens()->where('name', 'like', '%sharex%')->count())->toBe(1);
});

test('ShareX config download requires authentication', function () {
    $this->get(route('integrations.sharex'))
        ->assertRedirect(route('login'));
});

test('downloads a working Xerahs uploader config', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    $response = $this->actingAs($user)
        ->get(route('integrations.xerahs'))
        ->assertOk()
        ->assertDownload('jane-doe-xerahs.sxcu');

    $config = $response->json();

    expect($config['RequestMethod'])->toBe('POST');
    expect($config['RequestURL'])->toBe(route('api.v1.upload'));
    expect($config['FileFormName'])->toBe('file');
    expect($config['Headers']['Authorization'])->toStartWith('Bearer ');
    expect($config['URL'])->toBe('{json:data.preview_ext_url}');
    expect($config['DeletionURL'])->toBe('{json:data.deletion_url}');
});

test('issues a Xerahs token to the user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('integrations.xerahs'))->assertOk();

    expect($user->tokens()->where('name', 'like', 'Xerahs-%')->count())->toBe(1);
});

test('Xerahs config download requires authentication', function () {
    $this->get(route('integrations.xerahs'))
        ->assertRedirect(route('login'));
});

test('downloads a working ishare uploader config', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    $response = $this->actingAs($user)
        ->get(route('integrations.ishare'))
        ->assertOk()
        ->assertDownload('jane-doe-ishare.iscu');

    $config = $response->json();

    expect($config['requestURL'])->toBe(route('api.v1.upload'));
    expect($config['fileFormName'])->toBe('file');
    expect($config['requestBodyType'])->toBe('multipartFormData');
    expect($config['headers']['Accept'])->toBe('application/json');
    expect($config['headers']['Authorization'])->toStartWith('Bearer ');
    expect($config['responseURL'])->toBe('{{data.preview_ext_url}}');
    expect($config['deletionURL'])->toBe('{{data.deletion_url}}');
    expect($config['deleteRequestType'])->toBe('GET');
    // ishare parses plain JSON, so the Sanctum pipe must be left intact (unescaped).
    expect($config['headers']['Authorization'])->toContain('|');
});

test('names the ishare uploader after the instance and user', function () {
    config(['app.name' => 'Acme Shots']);

    $user = User::factory()->create(['name' => 'Jane Doe']);

    $config = $this->actingAs($user)
        ->get(route('integrations.ishare'))
        ->assertOk()
        ->json();

    expect($config['name'])->toBe('Acme Shots - Jane Doe');
});

test('issues an ishare token to the user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('integrations.ishare'))->assertOk();

    expect($user->tokens()->where('name', 'like', 'ishare-%')->count())->toBe(1);
});

test('ishare config download requires authentication', function () {
    $this->get(route('integrations.ishare'))
        ->assertRedirect(route('login'));
});

test('downloads a working CLI uploader script', function () {
    $user = User::factory()->create();

    $script = $this->actingAs($user)
        ->get(route('integrations.cli'))
        ->assertOk()
        ->assertDownload('xbb')
        ->getContent();

    expect($script)->toContain('#!/usr/bin/env bash');
    expect($script)->toContain(rtrim(config('app.url'), '/'));
    expect($script)->not->toContain('@@XBB_URL@@');
    expect($script)->not->toContain('@@XBB_TOKEN@@');
});

test('issues a CLI token to the user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('integrations.cli'))->assertOk();

    expect($user->tokens()->where('name', 'like', 'CLI-%')->count())->toBe(1);
});

test('CLI script download requires authentication', function () {
    $this->get(route('integrations.cli'))
        ->assertRedirect(route('login'));
});

test('downloads a working, self-contained KDE Share installer', function () {
    $user = User::factory()->create();

    $script = $this->actingAs($user)
        ->get(route('integrations.kde'))
        ->assertOk()
        ->assertDownload('xbackbone-kde-install.sh')
        ->getContent();

    // Self-contained: shebang plus the embedded metadata, Python uploader and icons.
    expect($script)->toContain('#!/usr/bin/env bash');
    expect($script)->toContain('kpackage/Purpose/xbackbone');
    expect($script)->toContain('"Name": "Upload to XBackBone"');
    expect($script)->toContain('X-Purpose-PluginTypes');
    expect($script)->toContain('/api/v1/upload');
    expect($script)->toContain('def run_purpose_job');
    expect($script)->toContain(base64_encode(file_get_contents(resource_path('integrations/kde/icons/xbackbone-32.png'))));

    // Credentials baked in; no leftover template markers.
    expect($script)->toContain(rtrim(config('app.url'), '/'));
    expect($script)->not->toContain('@@');
});

test('derives the KDE installer menu name from the instance app name', function () {
    config(['app.name' => "Acme Photo's Co."]);

    $user = User::factory()->create();

    $script = $this->actingAs($user)
        ->get(route('integrations.kde'))
        ->assertOk()
        ->getContent();

    expect($script)->toContain('"Name": "Upload to Acme Photo\'s Co."');
    expect($script)->toContain('"X-Purpose-ActionDisplay": "Upload to Acme Photo\'s Co."');
});

test('issues a KDE token when the plugin is downloaded', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('integrations.kde'))->assertOk();

    expect($user->tokens()->where('name', 'like', 'KDE-%')->count())->toBe(1);
});

test('KDE plugin download requires authentication', function () {
    $this->get(route('integrations.kde'))
        ->assertRedirect(route('login'));
});

test('downloads a working, self-contained macOS Share installer', function () {
    $user = User::factory()->create();

    $script = $this->actingAs($user)
        ->get(route('integrations.macos'))
        ->assertOk()
        ->assertDownload('xbackbone-macos-install.sh')
        ->getContent();

    // Self-contained: shebang plus the embedded uploader, shortcut and signing flow.
    expect($script)->toContain('#!/usr/bin/env bash');
    expect($script)->toContain('Library/Application Support/XBackBone');
    expect($script)->toContain('shortcuts sign --mode anyone');
    expect($script)->toContain('Allow Untrusted Shortcuts');
    expect($script)->toContain('plutil -convert binary1');
    expect($script)->toContain('Upload to XBackBone');
    expect($script)->toContain(base64_encode(file_get_contents(resource_path('integrations/xbb'))));

    // Credentials baked in; no leftover template markers.
    expect($script)->toContain(rtrim(config('app.url'), '/'));
    expect($script)->not->toContain('@@');
});

test('derives the macOS shortcut name from the instance app name', function () {
    config(['app.name' => "Acme Photo's Co."]);

    $user = User::factory()->create();

    $script = $this->actingAs($user)
        ->get(route('integrations.macos'))
        ->assertOk()
        ->getContent();

    expect($script)->toContain('SHORTCUT_NAME="Upload to Acme Photo\'s Co."');
});

test('issues a macOS token when the installer is downloaded', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('integrations.macos'))->assertOk();

    expect($user->tokens()->where('name', 'like', 'macOS-%')->count())->toBe(1);
});

test('macOS installer download requires authentication', function () {
    $this->get(route('integrations.macos'))
        ->assertRedirect(route('login'));
});

test('ScreenCloud plugin requires a valid signature', function () {
    $user = User::factory()->create();

    $this->get(route('integrations.screencloud', ['user' => $user->id]))
        ->assertForbidden();
});

test('serves a working ScreenCloud plugin over a signed url', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    $content = $this->get(URL::signedRoute('integrations.screencloud', ['user' => $user->id]))
        ->assertOk()
        ->assertDownload('jane-doe-screencloud.zip')
        ->getContent();

    $path = tempnam(sys_get_temp_dir(), 'sctest');
    file_put_contents($path, $content);

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();

    foreach (['main.py', 'metadata.xml', 'settings.ui', 'icon.png', 'config.json'] as $entry) {
        expect($zip->locateName($entry))->not->toBeFalse();
    }

    $config = json_decode($zip->getFromName('config.json'), true);
    $mainScript = $zip->getFromName('main.py');
    $metadata = $zip->getFromName('metadata.xml');
    $zip->close();
    @unlink($path);

    expect($config['host'])->toBe(rtrim(config('app.url'), '/'));
    expect($config['token'])->not->toBeEmpty();

    expect($mainScript)->toContain('class XBackBoneUploader:');
    expect($mainScript)->toContain("'User-Agent': 'XBackBone/Screencloud-client'");
    expect($mainScript)->not->toContain('@@SC_');

    expect($metadata)->toContain('<name>XBackBone Uploader 2.0</name>');
    expect($metadata)->toContain('<shortname>xbackbone</shortname>');
    expect($metadata)->toContain('<className>XBackBoneUploader</className>');
    expect($metadata)->not->toContain('@@SC_');
});

test('derives the ScreenCloud plugin name and class from the instance app name', function () {
    config(['app.name' => "Acme Photo's Co."]);

    $user = User::factory()->create();

    $content = $this->get(URL::signedRoute('integrations.screencloud', ['user' => $user->id]))
        ->assertOk()
        ->getContent();

    $path = tempnam(sys_get_temp_dir(), 'sctest');
    file_put_contents($path, $content);

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();

    $mainScript = $zip->getFromName('main.py');
    $metadata = $zip->getFromName('metadata.xml');
    $zip->close();
    @unlink($path);

    expect($mainScript)->toContain('class AcmePhotosCoUploader:');
    expect($mainScript)->toContain("'User-Agent': 'Acme Photo\\'s Co./Screencloud-client'");

    expect($metadata)->toContain('<shortname>acmephotosco</shortname>');
    expect($metadata)->toContain('<className>AcmePhotosCoUploader</className>');
    expect($metadata)->toContain('<name>Acme Photo&apos;s Co. Uploader 2.0</name>');
});

test('issues a ScreenCloud token when the plugin is fetched', function () {
    $user = User::factory()->create();

    $this->get(URL::signedRoute('integrations.screencloud', ['user' => $user->id]))->assertOk();

    expect($user->tokens()->where('name', 'like', 'ScreenCloud-%')->count())->toBe(1);
});
