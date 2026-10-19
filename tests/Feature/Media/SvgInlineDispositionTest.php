<?php

namespace Tests\Feature\Media;

use League\Flysystem\Filesystem;
use Tests\TestCase;

class SvgInlineDispositionTest extends TestCase
{
    private function storage(): Filesystem
    {
        return $this->app->getContainer()->get('storage');
    }

    private function createUpload(int $userId, string $filename, string $storagePath): int
    {
        $this->database()->query(
            'INSERT INTO `uploads`(`user_id`, `code`, `filename`, `storage_path`, `published`) VALUES (?, ?, ?, ?, 1)',
            [$userId, md5($filename), $filename, $storagePath]
        );

        return (int) $this->database()->getPdo()->lastInsertId();
    }

    private function login(string $username, string $password): void
    {
        $response = $this->post(route('login'), ['username' => $username, 'password' => $password]);
        $this->assertSame(302, $response->getStatusCode());
    }

    /** @test */
    public function an_svg_upload_is_not_served_inline_through_the_admin_raw_route()
    {
        $adminId = (int) $this->createAdminUser('admin@example.com', 'admin', 'admin-pass');
        $this->login('admin', 'admin-pass');

        $this->storage()->put(
            'test/poc.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>'
        );
        $mediaId = $this->createUpload($adminId, 'poc.svg', 'test/poc.svg');

        $response = $this->get(route('upload.raw', ['id' => $mediaId]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'text/plain',
            $response->getHeaderLine('Content-Type'),
            'SVG must not be rendered by the browser when served through /upload/{id}/raw.'
        );
    }

    /** @test */
    public function an_html_upload_is_not_served_inline_through_the_admin_raw_route()
    {
        $adminId = (int) $this->createAdminUser('admin@example.com', 'admin', 'admin-pass');
        $this->login('admin', 'admin-pass');

        $this->storage()->put('test/poc.html', '<html><script>alert(1)</script></html>');
        $mediaId = $this->createUpload($adminId, 'poc.html', 'test/poc.html');

        $response = $this->get(route('upload.raw', ['id' => $mediaId]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain', $response->getHeaderLine('Content-Type'));
    }

    /** @test */
    public function an_image_upload_keeps_its_own_content_type_through_the_admin_raw_route()
    {
        $adminId = (int) $this->createAdminUser('admin@example.com', 'admin', 'admin-pass');
        $this->login('admin', 'admin-pass');

        // 1x1 transparent PNG
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        $this->storage()->put('test/picture.png', $png);
        $mediaId = $this->createUpload($adminId, 'picture.png', 'test/picture.png');

        $response = $this->get(route('upload.raw', ['id' => $mediaId]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/png', $response->getHeaderLine('Content-Type'));
    }
}
