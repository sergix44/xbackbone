<?php


namespace Tests\Feature\Media;

use Tests\TestCase;

/**
 * Regression test for executable content types served from the instance.
 *
 * Uploaded XHTML, XML and XSLT documents were served with their detected
 * content type, which lets a browser execute script in the instance origin.
 * The admin-only /upload/{id}/raw route and the crawler branch of the public
 * page had no escaping check at all.
 */
class ExecutableContentTypeTest extends TestCase
{
    private function createUploader(string $username, string $token): int
    {
        return (int) $this->createUser([
            'email' => $username.'@example.com',
            'username' => $username,
            'password' => password_hash($username.'-pass', PASSWORD_DEFAULT),
            'is_admin' => 0,
            'token' => $token,
            'user_code' => $username,
        ]);
    }

    private function writeMedia(int $userId, string $userCode, string $code, string $extension, string $content): int
    {
        $dir = $this->storageRoot().'/'.$userCode;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir.'/'.$code.'.'.$extension, $content);

        $this->database()->query(
            'INSERT INTO `uploads`(`user_id`, `code`, `filename`, `storage_path`, `published`) VALUES (?, ?, ?, ?, 1)',
            [$userId, $code, 'file.'.$extension, $userCode.'/'.$code.'.'.$extension]
        );

        return (int) $this->database()->getPdo()->lastInsertId();
    }

    private function storageRoot(): string
    {
        return rtrim($this->app->getContainer()->get('config')['storage']['path'], '/');
    }

    private function loginAdmin(): void
    {
        $this->createAdminUser('admin@example.com', 'admin', 'admin-pass');

        $response = $this->post(route('login'), [
            'username' => 'admin',
            'password' => 'admin-pass',
        ]);

        $this->assertSame(302, $response->getStatusCode());
    }

    /** @test */
    public function xhtml_upload_is_served_as_plain_text_on_the_public_raw_route()
    {
        $userId = $this->createUploader('victim', 'victim-token');
        $this->writeMedia(
            $userId,
            'victim',
            'xssxhtml',
            'xhtml',
            '<?xml version="1.0"?><html xmlns="http://www.w3.org/1999/xhtml"><head><title>x</title></head><body><script>document.title="XSS"</script></body></html>'
        );

        $response = $this->get(route('public.raw', [
            'userCode' => 'victim',
            'mediaCode' => 'xssxhtml',
            'ext' => 'xhtml',
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('text/plain', $response->getHeaderLine('Content-Type'));
    }

    /** @test */
    public function xml_upload_is_served_as_plain_text_on_the_public_raw_route()
    {
        $userId = $this->createUploader('victim', 'victim-token');
        $this->writeMedia(
            $userId,
            'victim',
            'xssxml',
            'xml',
            '<?xml version="1.0"?><?xml-stylesheet type="text/xsl" href="/victim/xssxsl/raw.xsl"?><root><item>x</item></root>'
        );

        $response = $this->get(route('public.raw', [
            'userCode' => 'victim',
            'mediaCode' => 'xssxml',
            'ext' => 'xml',
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('text/plain', $response->getHeaderLine('Content-Type'));
    }

    /** @test */
    public function html_upload_is_served_as_plain_text_on_the_admin_raw_route()
    {
        $this->loginAdmin();
        $userId = (int) $this->database()->query('SELECT `id` FROM `users` WHERE `username` = ?', ['admin'])->fetch()->id;
        $mediaId = $this->writeMedia(
            $userId,
            'admin',
            'adminxsshtml',
            'html',
            '<html><body><script>document.title="XSS"</script></body></html>'
        );

        $response = $this->get(route('upload.raw', ['id' => $mediaId]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('text/plain', $response->getHeaderLine('Content-Type'));
    }

    /** @test */
    public function svg_upload_is_served_as_plain_text_on_the_admin_raw_route()
    {
        $this->loginAdmin();
        $userId = (int) $this->database()->query('SELECT `id` FROM `users` WHERE `username` = ?', ['admin'])->fetch()->id;
        $mediaId = $this->writeMedia(
            $userId,
            'admin',
            'adminxsssvg',
            'svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>document.title="XSS"</script></svg>'
        );

        $response = $this->get(route('upload.raw', ['id' => $mediaId]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('text/plain', $response->getHeaderLine('Content-Type'));
    }

    /** @test */
    public function crawler_user_client_gets_plain_text_for_an_html_upload()
    {
        $userId = $this->createUploader('victim', 'victim-token');
        $this->writeMedia(
            $userId,
            'victim',
            'crawlerxss',
            'html',
            '<html><body><script>document.title="XSS"</script></body></html>'
        );

        $response = $this->get(
            route('public', ['userCode' => 'victim', 'mediaCode' => 'crawlerxss']),
            [],
            [],
            ['user-client' => 'curl/8.0']
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('text/plain', $response->getHeaderLine('Content-Type'));
    }

    /** @test */
    public function an_image_upload_still_keeps_its_image_content_type()
    {
        $userId = $this->createUploader('victim', 'victim-token');
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        $this->writeMedia($userId, 'victim', 'picture', 'png', $png);

        $response = $this->get(route('public.raw', [
            'userCode' => 'victim',
            'mediaCode' => 'picture',
            'ext' => 'png',
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('image/png', $response->getHeaderLine('Content-Type'));
    }
}
