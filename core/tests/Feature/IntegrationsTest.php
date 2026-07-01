<?php

use App\Models\User;

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
        ->assertDontSee('Linux Desktop');
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
