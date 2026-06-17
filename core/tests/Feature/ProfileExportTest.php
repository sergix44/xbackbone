<?php

use App\Livewire\User\Profile;
use App\Models\Properties\ResourceType;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake();
});

/**
 * Extract a [entry name => contents] map from a raw ZIP binary by walking its
 * central directory. Implemented in pure PHP so the tests do not depend on the
 * optional `ext-zip` extension (the controller streams via maennchen/zipstream,
 * which is pure PHP too).
 *
 * @return array<string, string>
 */
function zipEntries(string $binary): array
{
    $eocd = strrpos($binary, "PK\x05\x06");
    $cursor = unpack('V', substr($binary, $eocd + 16, 4))[1]; // offset of the central directory

    $entries = [];
    while (substr($binary, $cursor, 4) === "PK\x01\x02") {
        $method = unpack('v', substr($binary, $cursor + 10, 2))[1];
        $compressedSize = unpack('V', substr($binary, $cursor + 20, 4))[1];
        $nameLength = unpack('v', substr($binary, $cursor + 28, 2))[1];
        $extraLength = unpack('v', substr($binary, $cursor + 30, 2))[1];
        $commentLength = unpack('v', substr($binary, $cursor + 32, 2))[1];
        $localOffset = unpack('V', substr($binary, $cursor + 42, 4))[1];
        $name = substr($binary, $cursor + 46, $nameLength);

        $localNameLength = unpack('v', substr($binary, $localOffset + 26, 2))[1];
        $localExtraLength = unpack('v', substr($binary, $localOffset + 28, 2))[1];
        $dataStart = $localOffset + 30 + $localNameLength + $localExtraLength;
        $data = substr($binary, $dataStart, $compressedSize);

        $entries[$name] = $method === 8 ? gzinflate($data) : $data;

        $cursor += 46 + $nameLength + $extraLength + $commentLength;
    }

    return $entries;
}

test('the export streams a zip of the user files with the original filenames', function () {
    $user = User::factory()->create();

    $a = Resource::factory()->for($user)->create(['filename' => 'alpha.png', 'fingerprint' => sha1('alpha')]);
    $b = Resource::factory()->for($user)->create(['filename' => 'beta.txt', 'fingerprint' => sha1('beta')]);
    Storage::put($a->storage_path, 'alpha-bytes');
    Storage::put($b->storage_path, 'beta-bytes');

    $response = $this->actingAs($user)->get(route('user.profile.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/zip')
        ->and($response->headers->get('content-disposition'))->toContain('-export.zip');

    $entries = zipEntries($response->streamedContent());

    expect($entries)->toHaveCount(2)
        ->and($entries['alpha.png'])->toBe('alpha-bytes')
        ->and($entries['beta.txt'])->toBe('beta-bytes');
});

test('the export only includes the authenticated user files', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $mine = Resource::factory()->for($user)->create(['filename' => 'mine.png', 'fingerprint' => sha1('mine')]);
    $theirs = Resource::factory()->for($other)->create(['filename' => 'theirs.png', 'fingerprint' => sha1('theirs')]);
    Storage::put($mine->storage_path, 'mine-bytes');
    Storage::put($theirs->storage_path, 'theirs-bytes');

    $response = $this->actingAs($user)->get(route('user.profile.export'))->assertOk();

    expect(zipEntries($response->streamedContent()))->toBe(['mine.png' => 'mine-bytes']);
});

test('the export disambiguates duplicate filenames', function () {
    $user = User::factory()->create();

    $a = Resource::factory()->for($user)->create(['filename' => 'dup.png', 'code' => 'aaa', 'fingerprint' => sha1('a')]);
    $b = Resource::factory()->for($user)->create(['filename' => 'dup.png', 'code' => 'bbb', 'fingerprint' => sha1('b')]);
    Storage::put($a->storage_path, 'a-bytes');
    Storage::put($b->storage_path, 'b-bytes');

    $response = $this->actingAs($user)->get(route('user.profile.export'))->assertOk();

    expect(zipEntries($response->streamedContent()))->toHaveKeys(['dup.png', 'dup-bbb.png']);
});

test('the export skips resources without a stored file', function () {
    $user = User::factory()->create();

    // A directory has no physical file (null fingerprint) and must be filtered out.
    Resource::factory()->for($user)->create(['type' => ResourceType::DIRECTORY, 'fingerprint' => null]);
    // A resource whose physical file is missing is skipped without erroring.
    Resource::factory()->for($user)->create(['filename' => 'gone.png', 'fingerprint' => sha1('gone')]);

    $present = Resource::factory()->for($user)->create(['filename' => 'here.png', 'fingerprint' => sha1('here')]);
    Storage::put($present->storage_path, 'here-bytes');

    $response = $this->actingAs($user)->get(route('user.profile.export'))->assertOk();

    expect(zipEntries($response->streamedContent()))->toBe(['here.png' => 'here-bytes']);
});

test('the export requires authentication', function () {
    $this->get(route('user.profile.export'))->assertRedirect(route('login'));
});

test('the export tab links to the download route', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class, ['tab' => 'export'])
        ->assertOk()
        ->assertSee(route('user.profile.export'), escape: false);
});
