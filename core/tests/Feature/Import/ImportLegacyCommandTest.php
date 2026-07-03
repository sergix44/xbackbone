<?php

use XBB\Jobs\GenerateResourcePreview;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Properties\UserStatus;
use XBB\Models\Resource;
use XBB\Models\User;
use Illuminate\Hashing\Argon2IdHasher;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\PendingCommand;

// A minimal 1x1 PNG so mime detection resolves to image/png from real bytes.
const LEGACY_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

/**
 * Build a throwaway legacy XBackBone SQLite database and its on-disk storage tree,
 * returning the paths and the admin's bcrypt hash for later assertions.
 *
 * @return array{db: string, storage: string, adminHash: string}
 */
function bootLegacyInstance(): array
{
    $base = sys_get_temp_dir().'/xbb-legacy-'.uniqid();
    $storage = $base.'/storage';

    @mkdir($storage.'/aa111', 0777, true);
    @mkdir($storage.'/bb222', 0777, true);
    @mkdir($storage.'/cc333', 0777, true);

    $png = base64_decode(LEGACY_PNG);
    file_put_contents($storage.'/aa111/pub001.png', $png);      // upload 1
    file_put_contents($storage.'/bb222/priv02.txt', 'notes');   // upload 2
    file_put_contents($storage.'/aa111/dupe03.png', $png);      // upload 3 (same bytes as 1)
    file_put_contents($storage.'/cc333/orph04.bin', 'orphan');  // upload 4 (orphaned)
    // upload 5 (miss05.bin) is intentionally absent on disk.

    $adminHash = password_hash('secret-pw', PASSWORD_DEFAULT); // bcrypt $2y$, like legacy

    $db = $base.'/legacy.sqlite';
    $pdo = new PDO('sqlite:'.$db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email VARCHAR(30) NOT NULL,
        username VARCHAR(30) NOT NULL,
        password VARCHAR(256) NOT NULL,
        user_code VARCHAR(5),
        token VARCHAR(256),
        active BOOLEAN NOT NULL DEFAULT 1,
        is_admin BOOLEAN NOT NULL DEFAULT 0,
        registration_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        max_disk_quota BIGINT NOT NULL DEFAULT -1
    )');
    $pdo->exec('CREATE TABLE uploads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        code VARCHAR(64) NOT NULL,
        filename VARCHAR(128) NOT NULL,
        storage_path VARCHAR(256) NOT NULL,
        published BOOLEAN NOT NULL DEFAULT 1,
        timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $insertUser = $pdo->prepare('INSERT INTO users (id, email, username, password, user_code, token, active, is_admin, registration_date, max_disk_quota) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insertUser->execute([1, 'admin@old.test', 'OldAdmin', $adminHash, 'aa111', 'token_a', 1, 1, '2020-01-02 03:04:05', -1]);
    $insertUser->execute([2, 'bob@old.test', 'Bob', password_hash('bob-pw', PASSWORD_DEFAULT), 'bb222', 'token_b', 0, 0, '2021-06-07 08:09:10', 1048576]);

    $insertUpload = $pdo->prepare('INSERT INTO uploads (id, user_id, code, filename, storage_path, published, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $insertUpload->execute([1, 1, 'pub001', 'photo.png', 'aa111/pub001.png', 1, '2020-02-03 04:05:06']);
    $insertUpload->execute([2, 2, 'priv02', 'notes.txt', 'bb222/priv02.txt', 0, '2021-07-08 09:10:11']);
    $insertUpload->execute([3, 1, 'dupe03', 'copy.png', 'aa111/dupe03.png', 1, '2020-03-04 05:06:07']);
    $insertUpload->execute([4, null, 'orph04', 'orphan.bin', 'cc333/orph04.bin', 1, '2020-04-05 06:07:08']);
    $insertUpload->execute([5, 1, 'miss05', 'gone.bin', 'aa111/miss05.bin', 1, '2020-05-06 07:08:09']);
    $pdo = null;

    return ['db' => $db, 'storage' => $storage, 'adminHash' => $adminHash];
}

function importLegacy(array $legacy, array $extra = []): PendingCommand
{
    return test()->artisan('xbackbone:import', array_merge([
        '--driver' => 'sqlite',
        '--db-file' => $legacy['db'],
        '--storage-path' => $legacy['storage'],
    ], $extra));
}

beforeEach(function () {
    Storage::fake();
    Queue::fake();
    $this->legacy = bootLegacyInstance();
});

test('it imports users with their mapped fields', function () {
    importLegacy($this->legacy)->assertSuccessful();

    expect(User::count())->toBe(2);

    $admin = User::where('email', 'admin@old.test')->sole();
    expect($admin->name)->toBe('OldAdmin')
        ->and($admin->is_admin)->toBeTrue()
        ->and($admin->status)->toBe(UserStatus::ENABLED)
        ->and($admin->quota)->toBe(-1)
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and($admin->created_at->toDateTimeString())->toBe('2020-01-02 03:04:05');

    $bob = User::where('email', 'bob@old.test')->sole();
    expect($bob->status)->toBe(UserStatus::DISABLED)
        ->and($bob->is_admin)->toBeFalse()
        ->and($bob->quota)->toBe(1048576);
});

test('it stores the legacy password verbatim and it verifies when algorithm checking is off', function () {
    importLegacy($this->legacy)->assertSuccessful();

    $admin = User::where('email', 'admin@old.test')->sole();

    // No double hashing: the bcrypt hash is carried over untouched.
    expect($admin->password)->toBe($this->legacy['adminHash']);

    // With HASH_VERIFY=false the bcrypt hash verifies (and Laravel rehashes on login).
    $hasher = new Argon2IdHasher(['verify' => false]);
    expect($hasher->check('secret-pw', $admin->password))->toBeTrue()
        ->and($hasher->check('wrong', $admin->password))->toBeFalse();
});

test('it imports media with mapped fields and content-addressed storage', function () {
    importLegacy($this->legacy)->assertSuccessful();

    // uploads 1,2,3 import; 4 is orphaned and 5 is missing.
    expect(Resource::count())->toBe(3);

    $admin = User::where('email', 'admin@old.test')->sole();
    $image = Resource::where('legacy_code', 'pub001')->sole();

    expect($image->type)->toBe(ResourceType::IMAGE)
        ->and($image->mime)->toBe('image/png')
        ->and($image->extension)->toBe('png')
        ->and($image->is_private)->toBeFalse()
        ->and($image->user_id)->toBe($admin->id)
        ->and($image->fingerprint)->toBe(sha1(base64_decode(LEGACY_PNG)))
        ->and($image->code)->not->toBeNull()
        ->and($image->preview_type)->toBeNull()
        ->and($image->published_at->toDateTimeString())->toBe('2020-02-03 04:05:06')
        ->and($image->created_at->toDateTimeString())->toBe('2020-02-03 04:05:06');

    Storage::assertExists($image->fingerprint);

    $text = Resource::where('legacy_code', 'priv02')->sole();
    expect($text->type)->toBe(ResourceType::TEXT)
        ->and($text->is_private)->toBeTrue();
});

test('it deduplicates identical files to a single stored object', function () {
    importLegacy($this->legacy)->assertSuccessful();

    $original = Resource::where('legacy_code', 'pub001')->sole();
    $duplicate = Resource::where('legacy_code', 'dupe03')->sole();

    expect($duplicate->fingerprint)->toBe($original->fingerprint)
        ->and($duplicate->code)->not->toBe($original->code);

    // Two unique contents (png + txt); the deduped png is stored once.
    expect(Storage::allFiles())->toHaveCount(2);
});

test('it skips orphaned uploads and missing files by default', function () {
    importLegacy($this->legacy)->assertSuccessful();

    expect(Resource::where('legacy_code', 'orph04')->exists())->toBeFalse()
        ->and(Resource::where('legacy_code', 'miss05')->exists())->toBeFalse();
});

test('it fails when a referenced file is missing and on-missing-file=fail', function () {
    importLegacy($this->legacy, ['--on-missing-file' => 'fail'])->assertFailed();
});

test('it assigns orphaned uploads to an admin when requested', function () {
    importLegacy($this->legacy, ['--orphans' => 'admin'])->assertSuccessful();

    $orphan = Resource::where('legacy_code', 'orph04')->sole();
    $admin = User::where('email', 'admin@old.test')->sole();

    expect($orphan->user_id)->toBe($admin->id);
});

test('it does not queue previews unless asked', function () {
    importLegacy($this->legacy)->assertSuccessful();

    Queue::assertNothingPushed();
    expect(Resource::where('legacy_code', 'pub001')->sole()->preview_type)->toBeNull();
});

test('it queues previews and marks them pending with --with-previews', function () {
    importLegacy($this->legacy, ['--with-previews' => true])->assertSuccessful();

    Queue::assertPushed(GenerateResourcePreview::class);
    expect(Resource::where('legacy_code', 'pub001')->sole()->preview_type)->toBe(ResourceType::FUTURE);
});

test('it is idempotent across repeated runs', function () {
    importLegacy($this->legacy)->assertSuccessful();
    $users = User::count();
    $resources = Resource::count();
    $files = count(Storage::allFiles());

    importLegacy($this->legacy)->assertSuccessful();

    expect(User::count())->toBe($users)
        ->and(Resource::count())->toBe($resources)
        ->and(Storage::allFiles())->toHaveCount($files);
});

test('dry-run writes nothing', function () {
    importLegacy($this->legacy, ['--dry-run' => true])->assertSuccessful();

    expect(User::count())->toBe(0)
        ->and(Resource::count())->toBe(0)
        ->and(Storage::allFiles())->toHaveCount(0);
});

test('it permanently redirects a legacy url to the current resource url', function () {
    importLegacy($this->legacy)->assertSuccessful();

    $resource = Resource::where('legacy_code', 'pub001')->sole();
    $target = route('preview', ['resource' => $resource->code]);

    $this->get('/aa111/pub001')->assertRedirect($target)->assertStatus(301);
    $this->get('/aa111/pub001.png')->assertRedirect($target)->assertStatus(301);
    $this->get('/aa111/unknown-code')->assertNotFound();
});
