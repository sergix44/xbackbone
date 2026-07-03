<?php

use XBB\Livewire\User\Profile;
use XBB\Models\Resource;
use XBB\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake();
});

test('deleting the account removes the user, their files and their tokens', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->for($user)->create(['fingerprint' => sha1('mine')]);
    Storage::put($resource->storage_path, 'mine-bytes');
    $user->createToken('test-token');

    $this->actingAs($user);

    Livewire::test(Profile::class, ['tab' => 'delete'])
        ->set('deletePassword', 'password')
        ->call('deleteAccount')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    expect(User::find($user->id))->toBeNull()
        ->and(Resource::find($resource->id))->toBeNull()
        ->and(PersonalAccessToken::count())->toBe(0);
    Storage::assertMissing($resource->storage_path);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'user.deleted',
        'subject_id' => $user->id,
        'causer_id' => $user->id,
    ]);
});

test('deleting the account keeps a file still referenced by another user', function () {
    $fingerprint = sha1('shared');
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $mine = Resource::factory()->for($owner)->create(['fingerprint' => $fingerprint]);
    $theirs = Resource::factory()->for($other)->create(['fingerprint' => $fingerprint]);
    Storage::put($mine->storage_path, 'shared-bytes');

    $this->actingAs($owner);

    Livewire::test(Profile::class, ['tab' => 'delete'])
        ->set('deletePassword', 'password')
        ->call('deleteAccount');

    expect(User::find($owner->id))->toBeNull()
        ->and(Resource::find($theirs->id))->not->toBeNull();
    Storage::assertExists($theirs->storage_path);
});

test('the account is not deleted with a wrong password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class, ['tab' => 'delete'])
        ->set('deletePassword', 'wrong-password')
        ->call('deleteAccount')
        ->assertHasErrors('deletePassword');

    expect(User::find($user->id))->not->toBeNull();
});
