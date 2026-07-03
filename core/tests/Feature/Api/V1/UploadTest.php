<?php

use XBB\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

test('upload a file', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [
            'file' => UploadedFile::fake()->image('screen.jpg'),
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'type',
                'filename',
                'mime',
                'size',
                'is_private',
                'extension',
                'view_count',
                'download_count',
                'preview_url',
                'preview_ext_url',
                'deletion_url',
                'published_at',
                'expires_at',
            ],
        ]);
});

test('upload a file string', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [
            'data' => 'ij j ewojfeiojwio eoje jwefjiwe jf ',
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'type',
                'filename',
                'mime',
                'size',
                'is_private',
                'extension',
                'view_count',
                'download_count',
                'preview_url',
                'preview_ext_url',
                'deletion_url',
                'published_at',
                'expires_at',
            ],
        ]);
});

test('shortens a url into a link resource', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [
            'data' => 'https://example.com/some/very/long/path',
        ])
        ->assertCreated();

    expect($response->json('data.type'))->toBe('LINK');
    expect($response->json('data.deletion_url'))->toContain('signature=');
});

test('fails when not authenticated', function () {
    $this->postJson(route('api.v1.upload'), [
        'file' => UploadedFile::fake()->image('screen.jpg'),
    ])
        ->assertUnauthorized();
});

test('fails file is not specified', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});
