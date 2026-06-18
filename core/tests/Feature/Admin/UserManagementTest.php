<?php

use App\Livewire\Admin\UserManagement;
use App\Models\Properties\UserStatus;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('non-admin users cannot mount the user management component', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(UserManagement::class)->assertForbidden();
});

test('admins can render the user management list', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin One']);
    User::factory()->create(['name' => 'Regular Joe']);

    $this->actingAs($admin);

    Livewire::test(UserManagement::class)
        ->assertSee('Admin One')
        ->assertSee('Regular Joe');
});

test('the list renders labelled action buttons and a date tooltip', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'Joined Jane']);

    $this->actingAs($admin);

    Livewire::test(UserManagement::class)
        ->assertSeeHtml('btn-error')
        ->assertSee(__('Edit'))
        ->assertSee(__('Delete'))
        ->assertSeeHtml('data-tip="'.e($user->created_at).'"')
        ->assertSee($user->created_at->diffForHumans());
});

test('the list can be searched by name or email', function () {
    $admin = User::factory()->admin()->create(['name' => 'Searcher']);
    User::factory()->create(['name' => 'Findable Frank', 'email' => 'frank@example.com']);
    User::factory()->create(['name' => 'Hidden Hannah', 'email' => 'hannah@example.com']);

    $this->actingAs($admin);

    Livewire::test(UserManagement::class)
        ->set('search', 'frank@example.com')
        ->assertSee('Findable Frank')
        ->assertDontSee('Hidden Hannah');
});

test('an admin can create a user with role, status and quota', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(UserManagement::class)
        ->call('openCreate')
        ->set('name', 'New Person')
        ->set('email', 'new@example.com')
        ->set('password', 'password123')
        ->set('isAdmin', true)
        ->set('status', UserStatus::API_ONLY->value)
        ->set('unlimitedQuota', false)
        ->set('quotaMb', 100)
        ->call('saveUser')
        ->assertHasNoErrors()
        ->assertSet('showUserModal', false);

    $user = User::query()->where('email', 'new@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->is_admin)->toBeTrue();
    expect($user->status)->toBe(UserStatus::API_ONLY);
    expect($user->quota)->toBe(100 * 1024 * 1024);
    expect($user->email_verified_at)->not->toBeNull();
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

test('an unlimited quota is stored as -1', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(UserManagement::class)
        ->call('openCreate')
        ->set('name', 'Unlimited')
        ->set('email', 'unlimited@example.com')
        ->set('password', 'password123')
        ->set('unlimitedQuota', true)
        ->call('saveUser')
        ->assertHasNoErrors();

    expect(User::query()->where('email', 'unlimited@example.com')->value('quota'))->toBe(-1);
});

test('creating a user with a duplicate email fails validation', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(UserManagement::class)
        ->call('openCreate')
        ->set('name', 'Dup')
        ->set('email', 'taken@example.com')
        ->set('password', 'password123')
        ->call('saveUser')
        ->assertHasErrors(['email']);
});

test('creating a user requires a password', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(UserManagement::class)
        ->call('openCreate')
        ->set('name', 'No Pass')
        ->set('email', 'nopass@example.com')
        ->set('password', null)
        ->call('saveUser')
        ->assertHasErrors(['password']);
});

test('an admin can edit a user without changing the password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
    $originalPassword = $user->password;

    $this->actingAs($admin);

    Livewire::test(UserManagement::class)
        ->call('openEdit', $user->id)
        ->assertSet('name', 'Old Name')
        ->assertSet('editingId', $user->id)
        ->set('name', 'New Name')
        ->set('email', 'updated@example.com')
        ->set('status', UserStatus::DISABLED->value)
        ->call('saveUser')
        ->assertHasNoErrors()
        ->assertSet('showUserModal', false);

    $user->refresh();

    expect($user->name)->toBe('New Name');
    expect($user->email)->toBe('updated@example.com');
    expect($user->status)->toBe(UserStatus::DISABLED);
    expect($user->password)->toBe($originalPassword);
});

test('editing a user keeps their own email valid', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'self@example.com']);

    $this->actingAs($admin);

    Livewire::test(UserManagement::class)
        ->call('openEdit', $user->id)
        ->set('name', 'Same Email')
        ->call('saveUser')
        ->assertHasNoErrors();
});

test('an admin can delete a user and their resources', function () {
    $admin = User::factory()->admin()->create();
    $victim = User::factory()->create();
    Resource::factory()->for($victim)->create();

    $this->actingAs($admin);

    Livewire::test(UserManagement::class)
        ->call('confirmDelete', $victim->id)
        ->assertSet('confirmingDelete', true)
        ->call('deleteUser')
        ->assertSet('confirmingDelete', false);

    expect(User::query()->find($victim->id))->toBeNull();
    expect(Resource::query()->where('user_id', $victim->id)->count())->toBe(0);
});

test('an admin cannot delete their own account from the panel', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(UserManagement::class)
        ->call('confirmDelete', $admin->id)
        ->call('deleteUser');

    expect(User::query()->find($admin->id))->not->toBeNull();
});

test('the last administrator cannot be demoted', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(UserManagement::class)
        ->call('openEdit', $admin->id)
        ->set('isAdmin', false)
        ->call('saveUser');

    expect($admin->fresh()->is_admin)->toBeTrue();
});
