<?php

use App\Actions\User\DeleteUserAccount;
use App\Livewire\Dashboard;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake();
});

test('uploading through the dashboard logs resource.uploaded', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->set('files', [UploadedFile::fake()->image('screen.jpg')])
        ->call('saveUpload', 0)
        ->assertHasNoErrors();

    $resource = Resource::query()->where('user_id', $user->id)->firstOrFail();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'resource.uploaded',
        'subject_type' => Resource::class,
        'subject_id' => $resource->id,
        'causer_type' => User::class,
        'causer_id' => $user->id,
    ]);
});

test('toggling visibility through the dashboard logs resource.hidden and resource.published', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->for($user)->create(['is_private' => false]);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)->call('toggleVisibility', $resource->id);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'resource.hidden',
        'subject_id' => $resource->id,
        'causer_id' => $user->id,
    ]);

    Livewire::test(Dashboard::class)->call('toggleVisibility', $resource->id);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'resource.published',
        'subject_id' => $resource->id,
        'causer_id' => $user->id,
    ]);
});

test('updating settings through the dashboard logs resource.updated', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->set('settingsId', $resource->id)
        ->set('settingsExpiresAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('saveSettings')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'resource.updated',
        'subject_id' => $resource->id,
        'causer_id' => $user->id,
    ]);
});

test('deleting through the dashboard logs resource.deleted', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->call('confirmDelete', $resource->id)
        ->call('deleteResource');

    $this->assertDatabaseHas('activity_log', [
        'description' => 'resource.deleted',
        'subject_id' => $resource->id,
        'causer_id' => $user->id,
    ]);
});

test('uploading through the API logs resource.uploaded (regression: API previously produced no activity)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.upload'), [
            'file' => UploadedFile::fake()->image('screen.jpg'),
        ])
        ->assertCreated();

    $resource = Resource::query()->where('user_id', $user->id)->firstOrFail();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'resource.uploaded',
        'subject_id' => $resource->id,
        'causer_id' => $user->id,
    ]);
});

test('deleting through the API as the owner logs resource.deleted (regression: API previously produced no activity)', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->for($user)->create();
    Storage::put($resource->storage_path, 'bytes');

    $this->actingAs($user)
        ->deleteJson(route('api.v1.resources.destroy', $resource->code))
        ->assertNoContent();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'resource.deleted',
        'subject_id' => $resource->id,
        'causer_id' => $user->id,
    ]);
});

test('deleting through the API as an admin on another users resource logs the admin as causer', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $resource = Resource::factory()->for($owner)->create();
    Storage::put($resource->storage_path, 'bytes');

    $this->actingAs($admin)
        ->deleteJson(route('api.v1.resources.destroy', $resource->code))
        ->assertNoContent();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'resource.deleted',
        'subject_id' => $resource->id,
        'causer_id' => $admin->id,
    ]);
});

test('deleting through the signed ShareX deletion URL logs resource.deleted with no causer (regression: previously produced no activity)', function () {
    $resource = Resource::factory()->create();
    Storage::put($resource->storage_path, 'bytes');

    $this->get(URL::signedRoute('resource.delete', ['resource' => $resource->code]))
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('activity_log', [
        'description' => 'resource.deleted',
        'subject_id' => $resource->id,
        'causer_id' => null,
    ]);
});

test('deleting a user account cascades resource.deleted rows attributed to the deleting admin', function () {
    $admin = User::factory()->admin()->create();
    $victim = User::factory()->create();
    $resource = Resource::factory()->for($victim)->create();

    app(DeleteUserAccount::class)($victim, $admin);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'resource.deleted',
        'subject_id' => $resource->id,
        'causer_id' => $admin->id,
    ]);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'user.deleted',
        'subject_id' => $victim->id,
        'causer_id' => $admin->id,
    ]);
});
