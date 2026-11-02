<?php

namespace Tests\Feature\Upload;

use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\ServerRequest;
use Tests\TestCase;

class PathTraversalTest extends TestCase
{
    /** @test */
    public function custom_code_cannot_traverse_user_directories()
    {
        $this->createUser([
            'email' => 'victim@example.com',
            'username' => 'victim',
            'password' => password_hash('victim', PASSWORD_DEFAULT),
            'is_admin' => 0,
            'token' => 'tokenvictim',
            'user_code' => 'victimcode',
        ]);
        $this->createUser([
            'email' => 'attacker@example.com',
            'username' => 'attacker',
            'password' => password_hash('attacker', PASSWORD_DEFAULT),
            'is_admin' => 0,
            'token' => 'tokenattacker',
            'user_code' => 'attackercode',
        ]);

        $target = BASE_DIR.'storage/test/victimcode/pwned.txt';
        @unlink($target);

        $stream = \GuzzleHttp\Psr7\Utils::streamFor('ATTACKERDATA');
        $file = new UploadedFile($stream, 12, UPLOAD_ERR_OK, 'file.txt', 'text/plain');

        $request = new ServerRequest('POST', route('upload'), [], null, '1.1', ['CONTENT_LENGTH' => 12]);
        $request = $request->withParsedBody(['token' => 'tokenattacker', 'code' => '../victimcode/pwned']);
        $request = $request->withUploadedFiles(['file' => $file]);

        $response = $this->app->handle($request);
        if ($response->getBody()->isSeekable()) { $response->getBody()->rewind(); }

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFileDoesNotExist($target);
    }

    /** @test */
    public function custom_code_normal_upload_still_works()
    {
        $this->createUser([
            'email' => 'user@example.com',
            'username' => 'user',
            'password' => password_hash('user', PASSWORD_DEFAULT),
            'is_admin' => 0,
            'token' => 'tokennormal',
            'user_code' => 'usercode',
        ]);

        $target = BASE_DIR.'storage/test/usercode/mycode.txt';
        @unlink($target);

        $stream = \GuzzleHttp\Psr7\Utils::streamFor('HELLO');
        $file = new UploadedFile($stream, 5, UPLOAD_ERR_OK, 'file.txt', 'text/plain');

        $request = new ServerRequest('POST', route('upload'), [], null, '1.1', ['CONTENT_LENGTH' => 5]);
        $request = $request->withParsedBody(['token' => 'tokennormal', 'code' => 'mycode']);
        $request = $request->withUploadedFiles(['file' => $file]);

        $response = $this->app->handle($request);
        if ($response->getBody()->isSeekable()) { $response->getBody()->rewind(); }

        $this->assertSame(201, $response->getStatusCode());
    }
}
