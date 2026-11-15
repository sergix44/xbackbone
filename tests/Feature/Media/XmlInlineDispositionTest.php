<?php

namespace Tests\Feature\Media;

use League\Flysystem\Filesystem;
use Tests\TestCase;

class XmlInlineDispositionTest extends TestCase
{
    private function storage(): Filesystem
    {
        return $this->app->getContainer()->get('storage');
    }

    private function createUpload(int $userId, string $code, string $filename, string $storagePath): int
    {
        $this->database()->query(
            'INSERT INTO `uploads`(`user_id`, `code`, `filename`, `storage_path`, `published`) VALUES (?, ?, ?, ?, 1)',
            [$userId, $code, $filename, $storagePath]
        );

        return (int) $this->database()->getPdo()->lastInsertId();
    }

    private function login(string $username, string $password): void
    {
        $response = $this->post(route('login'), ['username' => $username, 'password' => $password]);
        $this->assertSame(302, $response->getStatusCode());
    }

    /** @test */
    public function an_xhtml_polyglot_upload_is_not_served_inline_through_the_public_raw_route()
    {
        $userId = (int) $this->createUser([
            'email' => 'user@example.com',
            'username' => 'user',
            'password' => password_hash('user-pass', PASSWORD_DEFAULT),
            'is_admin' => 0,
            'user_code' => 'test1',
        ]);

        // Same payload verified end to end on a fresh 3.8.2 install: finfo
        // reports it as text/xml and a real browser executes the script.
        $this->storage()->put(
            'test/poc.xhtml',
            '<?xml version="1.0" encoding="UTF-8"?><html xmlns="http://www.w3.org/1999/xhtml"><head><title>probe</title></head><body><p>hello</p><script>alert(document.domain);document.title=\'XSS-EXECUTED\';document.documentElement.setAttribute(\'data-xss\',\'1\');</script></body></html>'
        );
        $this->createUpload($userId, 'testcode', 'poc.xhtml', 'test/poc.xhtml');

        $response = $this->get(route('public.raw', ['userCode' => 'test1', 'mediaCode' => 'testcode', 'ext' => 'xhtml']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'text/plain',
            $response->getHeaderLine('Content-Type'),
            'XHTML polyglot must not be rendered by the browser when served through /{userCode}/{mediaCode}/raw.'
        );
    }

    /** @test */
    public function a_plain_xml_upload_is_not_served_inline_through_the_public_raw_route()
    {
        $userId = (int) $this->createUser([
            'email' => 'user@example.com',
            'username' => 'user',
            'password' => password_hash('user-pass', PASSWORD_DEFAULT),
            'is_admin' => 0,
            'user_code' => 'test1',
        ]);

        $this->storage()->put('test/note.xml', '<?xml version="1.0"?><note><to>user</to><from>attacker</from></note>');
        $this->createUpload($userId, 'testcode', 'note.xml', 'test/note.xml');

        $response = $this->get(route('public.raw', ['userCode' => 'test1', 'mediaCode' => 'testcode', 'ext' => 'xml']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain', $response->getHeaderLine('Content-Type'));
    }

    /** @test */
    public function an_xhtml_polyglot_upload_is_not_served_inline_through_the_admin_raw_route()
    {
        $adminId = (int) $this->createAdminUser('admin@example.com', 'admin', 'admin-pass');
        $this->login('admin', 'admin-pass');

        $this->storage()->put(
            'test/poc.xhtml',
            '<?xml version="1.0" encoding="UTF-8"?><html xmlns="http://www.w3.org/1999/xhtml"><head><title>probe</title></head><body><p>hello</p><script>alert(document.domain);document.title=\'XSS-EXECUTED\';document.documentElement.setAttribute(\'data-xss\',\'1\');</script></body></html>'
        );
        $mediaId = $this->createUpload($adminId, 'testcode', 'poc.xhtml', 'test/poc.xhtml');

        $response = $this->get(route('upload.raw', ['id' => $mediaId]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'text/plain',
            $response->getHeaderLine('Content-Type'),
            'XHTML polyglot must not be rendered by the browser when served through /upload/{id}/raw.'
        );
    }

    /** @test */
    public function an_html_upload_is_not_served_inline_through_the_public_raw_route()
    {
        $userId = (int) $this->createUser([
            'email' => 'user@example.com',
            'username' => 'user',
            'password' => password_hash('user-pass', PASSWORD_DEFAULT),
            'is_admin' => 0,
            'user_code' => 'test1',
        ]);

        $this->storage()->put('test/poc.html', '<html><script>alert(1)</script></html>');
        $this->createUpload($userId, 'testcode', 'poc.html', 'test/poc.html');

        $response = $this->get(route('public.raw', ['userCode' => 'test1', 'mediaCode' => 'testcode', 'ext' => 'html']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain', $response->getHeaderLine('Content-Type'));
    }

    /** @test */
    public function an_image_upload_keeps_its_own_content_type_through_the_public_raw_route()
    {
        $userId = (int) $this->createUser([
            'email' => 'user@example.com',
            'username' => 'user',
            'password' => password_hash('user-pass', PASSWORD_DEFAULT),
            'is_admin' => 0,
            'user_code' => 'test1',
        ]);

        // 1x1 transparent PNG
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        $this->storage()->put('test/picture.png', $png);
        $this->createUpload($userId, 'testcode', 'picture.png', 'test/picture.png');

        $response = $this->get(route('public.raw', ['userCode' => 'test1', 'mediaCode' => 'testcode', 'ext' => 'png']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/png', $response->getHeaderLine('Content-Type'));
    }
}
