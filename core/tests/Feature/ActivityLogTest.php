<?php

use Livewire\Livewire;
use XBB\Livewire\ActivityLog;
use XBB\Models\Resource;
use XBB\Models\User;

/**
 * Log an activity performed on a resource by the given causer.
 */
function logResourceActivity(string $description, User $causer, Resource $subject): void
{
    activity()->performedOn($subject)->causedBy($causer)->event($description)->log($description);
}

test('the global feed shows activity from every user', function () {
    $admin = User::factory()->admin()->create();
    $alice = User::factory()->create(['name' => 'Alice Uploader']);
    $bob = User::factory()->create(['name' => 'Bob Uploader']);

    logResourceActivity('resource.uploaded', $alice, Resource::factory()->for($alice)->create(['name' => 'Alpha File']));
    logResourceActivity('resource.uploaded', $bob, Resource::factory()->for($bob)->create(['name' => 'Bravo File']));

    $this->actingAs($admin);

    Livewire::test(ActivityLog::class)
        ->assertSee('Alice Uploader')
        ->assertSee('Bob Uploader')
        ->assertSee('Alpha File')
        ->assertSee('Bravo File')
        ->assertSee('Uploaded a file');
});

test('a scoped feed only shows the given users activity', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    logResourceActivity('resource.uploaded', $alice, Resource::factory()->for($alice)->create(['name' => 'Alpha File']));
    logResourceActivity('resource.uploaded', $bob, Resource::factory()->for($bob)->create(['name' => 'Bravo File']));

    $this->actingAs($alice);

    Livewire::test(ActivityLog::class, ['causerId' => $alice->id])
        ->assertSee('Alpha File')
        ->assertDontSee('Bravo File');
});

test('the category filter narrows the feed to a single event namespace', function () {
    $admin = User::factory()->admin()->create();

    logResourceActivity('resource.uploaded', $admin, Resource::factory()->for($admin)->create(['name' => 'Alpha File']));
    activity()->causedBy($admin)->event('auth.login')->log('auth.login');

    $this->actingAs($admin);

    Livewire::test(ActivityLog::class)
        ->set('category', 'auth')
        ->assertSee('Signed in')
        ->assertDontSee('Uploaded a file');
});

test('the global feed can be searched by causer name', function () {
    $admin = User::factory()->admin()->create();
    $alice = User::factory()->create(['name' => 'Alice Uploader']);
    $bob = User::factory()->create(['name' => 'Bob Uploader']);

    logResourceActivity('resource.uploaded', $alice, Resource::factory()->for($alice)->create(['name' => 'Alpha File']));
    logResourceActivity('resource.uploaded', $bob, Resource::factory()->for($bob)->create(['name' => 'Bravo File']));

    $this->actingAs($admin);

    Livewire::test(ActivityLog::class)
        ->set('search', 'Alice')
        ->assertSee('Alpha File')
        ->assertDontSee('Bravo File');
});

test('a non-admin cannot widen the feed beyond their own activity even by tampering with causerId', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    logResourceActivity('resource.uploaded', $alice, Resource::factory()->for($alice)->create(['name' => 'Alpha File']));
    logResourceActivity('resource.uploaded', $bob, Resource::factory()->for($bob)->create(['name' => 'Bravo File']));

    $this->actingAs($alice);

    // Simulate a tampered request that nulls the scope to request the global feed.
    Livewire::test(ActivityLog::class, ['causerId' => $alice->id])
        ->set('causerId', null)
        ->assertSee('Alpha File')
        ->assertDontSee('Bravo File');
});

test('the admin settings activity tab renders the feed', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings', ['tab' => 'activity']))
        ->assertOk()
        ->assertSeeLivewire(ActivityLog::class);
});

test('the profile activity tab renders the feed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('user.profile', ['tab' => 'activity']))
        ->assertOk()
        ->assertSeeLivewire(ActivityLog::class);
});
