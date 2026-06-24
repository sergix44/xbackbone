<?php

use App\Livewire\User\Profile;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Livewire\Livewire;

/**
 * Persist a passkey for the given user. The cryptographic credential is opaque
 * to our app, so a stub payload is enough for wiring/ownership assertions.
 */
function makePasskey(User $user, string $name = 'Test key'): Passkey
{
    return $user->passkeys()->create([
        'name' => $name,
        'credential_id' => Str::random(40),
        'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
    ]);
}

test('the passkeys feature is enabled', function () {
    expect(Features::enabled(Features::passkeys()))->toBeTrue();
});

test('the User model fulfils the passkey contract', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(PasskeyUser::class)
        ->and($user->hasPasskeysEnabled())->toBeFalse();

    makePasskey($user);

    expect($user->hasPasskeysEnabled())->toBeTrue()
        ->and($user->passkeys()->count())->toBe(1)
        ->and($user->getPasskeyUsername())->toBe($user->email);
});

test('a guest can fetch passkey login options', function () {
    $this->getJson(route('passkey.login-options'))
        ->assertOk()
        ->assertJsonStructure(['options']);
});

test('an authenticated user can fetch passkey registration options', function () {
    $this->actingAs(User::factory()->create())
        ->getJson(route('passkey.registration-options'))
        ->assertOk()
        ->assertJsonStructure(['options']);
});

test('the login page offers a passkey sign-in button', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Login with a passkey');
});

test('the profile passkeys tab lists the user passkeys', function () {
    $user = User::factory()->create();
    makePasskey($user, 'MacBook Touch ID');
    makePasskey($user, 'YubiKey');

    $this->actingAs($user);

    Livewire::test(Profile::class, ['tab' => 'passkeys'])
        ->assertOk()
        ->assertSee('MacBook Touch ID')
        ->assertSee('YubiKey');
});

test('a user can delete their own passkey', function () {
    $user = User::factory()->create();
    $passkey = makePasskey($user);

    $this->actingAs($user);

    Livewire::test(Profile::class, ['tab' => 'passkeys'])
        ->call('deletePasskey', $passkey->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('passkeys', ['id' => $passkey->id]);
});

test('a user cannot delete a passkey belonging to someone else', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $passkey = makePasskey($owner);

    $this->actingAs($attacker);

    Livewire::test(Profile::class, ['tab' => 'passkeys'])
        ->call('deletePasskey', $passkey->id);

    $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
});
