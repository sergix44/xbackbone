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
        ->assertSee('Spectacle')
        ->assertSee('Capture apps')
        ->assertSee('CLI scripts')
        ->assertSee('portable shell uploader')
        ->assertSee('https://getsharex.com/')
        ->assertSee('https://xerahs.com')
        ->assertSee('https://screencloud.net')
        ->assertSee('https://apps.kde.org/spectacle/')
        ->assertSee('Copy install link')
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
    $zip->close();
    @unlink($path);

    expect($config['host'])->toBe(rtrim(config('app.url'), '/'));
    expect($config['token'])->not->toBeEmpty();
});

test('issues a ScreenCloud token when the plugin is fetched', function () {
    $user = User::factory()->create();

    $this->get(URL::signedRoute('integrations.screencloud', ['user' => $user->id]))->assertOk();

    expect($user->tokens()->where('name', 'like', 'ScreenCloud-%')->count())->toBe(1);
});
