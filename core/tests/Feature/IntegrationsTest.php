<?php

use App\Models\User;

test('integrations page renders all available integrations', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('integrations'))
        ->assertOk()
        ->assertSee('Integrations')
        ->assertSee('ShareX')
        ->assertSee('ScreenCloud')
        ->assertSee('Linux Desktop')
        ->assertSee('KDE')
        ->assertSee('Capture apps')
        ->assertSee('Desktop scripts');
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
