<?php

use App\Models\Resource;
use Illuminate\Http\Request;

it('redirects a legacy two-segment URL to the current preview URL', function () {
    $resource = Resource::factory()->create(['legacy_code' => 'oldcode']);

    $this->get('/someuser/oldcode')
        ->assertRedirect(route('preview', ['resource' => $resource->code]))
        ->assertStatus(301);
});

it('ignores the legacy extension and matches on the bare code', function () {
    $resource = Resource::factory()->create(['legacy_code' => 'oldcode']);

    $this->get('/someuser/oldcode.png')
        ->assertRedirect(route('preview', ['resource' => $resource->code]))
        ->assertStatus(301);
});

it('returns 404 for an unknown legacy code', function () {
    $this->get('/someuser/missing')->assertNotFound();
});

it('does not shadow the Scramble API docs route', function () {
    $route = app('router')->getRoutes()->match(Request::create('/docs/api', 'GET'));

    expect($route->getName())->toBe('scramble.docs.ui');
});

it('does not capture API routes as legacy links', function () {
    $this->getJson('/api/v1/this-endpoint-does-not-exist')->assertNotFound();
});
