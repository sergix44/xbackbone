<?php

use XBB\Livewire\Preview;
use XBB\Models\Resource;
use XBB\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('a locked download is denied to a guest with 403', function () {
    Storage::fake();
    $resource = Resource::factory()->passwordProtected()->create();
    Storage::put($resource->storage_path, 'content');

    $this->get(route('download', ['resource' => $resource->code]))->assertForbidden();
});

test('a locked thumbnail is denied to a guest with 403', function () {
    $resource = Resource::factory()->image()->passwordProtected()->create();

    $this->get(route('thumbnail', ['resource' => $resource->code]))->assertForbidden();
});

test('a locked raw request redirects a guest to the preview', function () {
    $resource = Resource::factory()->image()->passwordProtected()->create();

    $this->get(route('raw', ['resource' => $resource->code]))
        ->assertRedirect(route('preview', ['resource' => $resource->code]));
});

test('the owner bypasses the password on serving routes', function () {
    Storage::fake();
    $owner = User::factory()->create();
    $resource = Resource::factory()->for($owner)->passwordProtected()->create();
    Storage::put($resource->storage_path, 'content');

    $this->actingAs($owner)
        ->get(route('download', ['resource' => $resource->code]))
        ->assertOk();
});

test('an admin bypasses the password on serving routes', function () {
    Storage::fake();
    $resource = Resource::factory()->passwordProtected()->create();
    Storage::put($resource->storage_path, 'content');

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get(route('download', ['resource' => $resource->code]))
        ->assertOk();
});

test('an unlocked session can reach the serving routes', function () {
    Storage::fake();
    $resource = Resource::factory()->passwordProtected()->create();
    Storage::put($resource->storage_path, 'content');

    $this->withSession([$resource->unlockSessionKey() => true])
        ->get(route('download', ['resource' => $resource->code]))
        ->assertOk();
});

test('a locked download is not counted, but counts once unlocked', function () {
    Storage::fake();
    $resource = Resource::factory()->passwordProtected()->create();
    Storage::put($resource->storage_path, 'content');

    $this->get(route('download', ['resource' => $resource->code]))->assertForbidden();
    expect($resource->fresh()->downloads)->toBe(0);

    $this->withSession([$resource->unlockSessionKey() => true])
        ->get(route('download', ['resource' => $resource->code]))
        ->assertOk();
    expect($resource->fresh()->downloads)->toBe(1);
});

test('the preview shows an unlock form and hides content while locked', function () {
    $resource = Resource::factory()->image()->passwordProtected()->create();

    $this->get(route('preview', ['resource' => $resource->code]))
        ->assertOk()
        ->assertSee('This resource is protected')
        ->assertDontSee($resource->raw_url, false);

    expect($resource->fresh()->views)->toBe(0);
});

test('unlocking with the wrong password keeps the resource locked', function () {
    $resource = Resource::factory()->image()->passwordProtected()->create();

    Livewire::test(Preview::class, ['resource' => $resource])
        ->assertSet('locked', true)
        ->set('passwordInput', 'wrong')
        ->call('unlock')
        ->assertHasErrors('passwordInput')
        ->assertSet('locked', true);

    expect($resource->fresh()->views)->toBe(0);
});

test('unlocking with the correct password redirects to the preview and counts a view once rendered', function () {
    $resource = Resource::factory()->image()->passwordProtected()->create();

    Livewire::test(Preview::class, ['resource' => $resource])
        ->set('passwordInput', 'secret')
        ->call('unlock')
        ->assertHasNoErrors()
        ->assertRedirect(route('preview', ['resource' => $resource->code]));

    // The view is recorded when the now-unlocked preview is rendered, not by the
    // unlock action itself.
    expect($resource->fresh()->views)->toBe(0);

    $this->withSession([$resource->unlockSessionKey() => true])
        ->get(route('preview', ['resource' => $resource->code]))
        ->assertOk();

    expect($resource->fresh()->views)->toBe(1);
});

test('the unlock action records no view itself', function () {
    $resource = Resource::factory()->image()->passwordProtected()->create();

    Livewire::test(Preview::class, ['resource' => $resource])
        ->set('passwordInput', 'secret')
        ->call('unlock')
        ->call('unlock');

    expect($resource->fresh()->views)->toBe(0);
});

test('the owner sees a protected resource without the unlock form', function () {
    $owner = User::factory()->create();
    $resource = Resource::factory()->image()->for($owner)->passwordProtected()->create();

    $this->actingAs($owner);

    Livewire::test(Preview::class, ['resource' => $resource])
        ->assertSet('locked', false);

    expect($resource->fresh()->views)->toBe(1);
});

test('password attempts are rate limited', function () {
    $resource = Resource::factory()->image()->passwordProtected()->create();

    $component = Livewire::test(Preview::class, ['resource' => $resource]);

    for ($i = 0; $i < 5; $i++) {
        $component->set('passwordInput', 'wrong')->call('unlock');
    }

    // Even the correct password is now blocked by the throttle.
    $component->set('passwordInput', 'secret')->call('unlock')
        ->assertHasErrors('passwordInput')
        ->assertSet('locked', true);

    expect($resource->fresh()->views)->toBe(0);
});

test('a private and protected resource shows 404 to strangers, not a form', function () {
    $resource = Resource::factory()->image()->passwordProtected()->create(['is_private' => true]);

    $this->get(route('preview', ['resource' => $resource->code]))->assertNotFound();
});
