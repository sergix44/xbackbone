<?php


namespace Tests\Feature\Media;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

/**
 * Regression test for the SQL injection that was reachable through the
 * upload "code" parameter / the client supplied file name.
 *
 * UploadController::saveMedia() built the storage path of an upload as
 * "<user_code>/<code>.<extension>" without validating <code> nor
 * <extension>, and MediaRepository::runWithFileSort() concatenated the names
 * of the files found in the storage straight into a SQL statement. A
 * registered (non privileged) user could therefore store a file whose name
 * contained SQL syntax and have that syntax executed as soon as somebody
 * opened the gallery sorted by size.
 */
class StoragePathSqlInjectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Every test starts from an empty storage and an empty flysystem
        // cache, otherwise a cached directory listing would hide the
        // injected file.
        $this->removeRecursively($this->storageRoot());
        $this->removeRecursively(BASE_DIR.'resources/cache/fs');
    }

    /**
     * The upload endpoint must not accept a "code" (or a file name) that is
     * able to escape the storage path.
     *
     * @test
     */
    public function a_registered_user_cannot_store_a_file_whose_name_contains_sql_syntax()
    {
        $this->createAttacker();

        $response = $this->upload('token_attacker', 'payload.png', $this->payload('benign.png'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringNotContainsString('UNION SELECT', $response->getBody()->getContents());
    }

    /**
     * End to end proof: a low privileged user stores a file whose name
     * contains a UNION statement and then reads the password hash of the
     * administrator out of the gallery rendered for his own account.
     *
     * @test
     */
    public function a_stored_payload_cannot_be_used_to_read_the_users_table()
    {
        $adminHash = $this->createAdminWithSecret('admin-secret');
        $this->createAttacker();

        // A harmless file owned by the attacker, used as the anchor of the injection.
        $this->upload('token_attacker', 'benign.png', 'benign');

        // The payload. The "/" of the target path is built with char(47) so
        // that the payload itself stays a plain file name and does not create
        // a sub directory.
        $this->upload('token_attacker', 'payload.png', $this->payload('benign.png'));

        $this->login('attacker', 'attacker-pass');

        $gallery = $this->get(route('home'), ['sort' => 'size']);

        $this->assertSame(200, $gallery->getStatusCode());
        $this->assertStringNotContainsString(
            $adminHash,
            $gallery->getBody()->getContents(),
            'The SQL injected through the stored file name was executed and leaked the password hash of the administrator.'
        );
    }

    /**
     * Defence in depth: even if a file with a hostile name is already present
     * in the storage (e.g. uploaded before the validation was added, or
     * placed there by any other means), the media query must treat the file
     * name as a plain value.
     *
     * @test
     */
    public function a_hostile_file_name_already_present_in_the_storage_is_not_executed()
    {
        $adminHash = $this->createAdminWithSecret('admin-secret');
        $this->createAttacker();

        $this->storeFileDirectly('atk01', 'benign.png');
        $this->storeFileDirectly('atk01', $this->payload('benign.png').'.png');

        $this->login('attacker', 'attacker-pass');

        $gallery = $this->get(route('home'), ['sort' => 'size']);

        $this->assertSame(200, $gallery->getStatusCode());
        $this->assertStringNotContainsString(
            $adminHash,
            $gallery->getBody()->getContents(),
            'The SQL injected through the stored file name was executed and leaked the password hash of the administrator.'
        );
    }

    /**
     * The fix must not break the legitimate usage of the "code" parameter.
     *
     * @test
     */
    public function a_regular_upload_with_a_custom_code_is_still_accepted()
    {
        $this->createAttacker();

        $response = $this->upload('token_attacker', 'holiday photo.png', 'my-code_1');

        $this->assertSame(201, $response->getStatusCode());
    }

    /**
     * Builds the SQL payload. It closes the string literal of the generated
     * "IN (...)" list and appends an UNION statement that copies the password
     * column of the administrators into the "filename" column of the result,
     * so that it is rendered in the gallery.
     */
    private function payload(string $targetFileName): string
    {
        return 'x") UNION ALL SELECT id, id, username, password, \'atk01\' || char(47) || \''.$targetFileName.'\', 1, registration_date, user_code, username FROM users WHERE is_admin = 1 -- ';
    }

    private function createAdminWithSecret(string $password): string
    {
        $this->createUser([
            'email' => 'admin@example.com',
            'username' => 'admin',
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'is_admin' => 1,
            'active' => 1,
            'user_code' => 'adm01',
            'token' => 'token_admin',
        ]);

        return $this->database()->query('SELECT `password` FROM `users` WHERE `username` = ?', ['admin'])->fetch()->password;
    }

    private function createAttacker(): void
    {
        $this->createUser([
            'email' => 'attacker@example.com',
            'username' => 'attacker',
            'password' => password_hash('attacker-pass', PASSWORD_DEFAULT),
            'is_admin' => 0,
            'active' => 1,
            'user_code' => 'atk01',
            'token' => 'token_attacker',
        ]);
    }

    private function login(string $username, string $password): void
    {
        $response = $this->post(route('login'), ['username' => $username, 'password' => $password]);
        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * Performs a real multipart upload against the public upload endpoint.
     */
    private function upload(string $token, string $clientFileName, ?string $code = null): ResponseInterface
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xbbtest');
        file_put_contents($tmp, str_repeat('A', 128));

        $files = [
            'upload' => [
                'name' => $clientFileName,
                'type' => 'application/octet-stream',
                'tmp_name' => $tmp,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($tmp),
            ],
        ];

        $body = ['token' => $token];
        if ($code !== null) {
            $body['code'] = $code;
        }

        $request = (new ServerRequest('POST', route('upload'), [], http_build_query($body), '1.1', ['CONTENT_LENGTH' => (string) filesize($tmp)]))
            ->withParsedBody($body)
            ->withUploadedFiles(ServerRequest::normalizeFiles($files));

        $response = $this->app->handle($request);

        if ($response->getBody()->isSeekable()) {
            $response->getBody()->rewind();
        }

        return $response;
    }

    /**
     * Writes a file directly in the storage, bypassing the upload endpoint.
     */
    private function storeFileDirectly(string $userCode, string $fileName): void
    {
        $dir = $this->storageRoot().'/'.$userCode;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir.'/'.$fileName, str_repeat('A', 128));

        $this->database()->query(
            'INSERT INTO `uploads`(`user_id`, `code`, `filename`, `storage_path`, `published`) VALUES (?, ?, ?, ?, 1)',
            [2, 'c'.md5($fileName), $fileName, $userCode.'/'.$fileName]
        );
    }

    private function storageRoot(): string
    {
        $path = $this->app->getContainer()->get('config')['storage']['path'];

        return strpos($path, '/') === 0 ? $path : BASE_DIR.$path;
    }

    private function removeRecursively(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
