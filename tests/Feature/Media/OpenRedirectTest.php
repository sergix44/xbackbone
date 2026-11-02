<?php


namespace Tests\Feature\Media;

use Tests\TestCase;

/**
 * Regression test for the open redirect through the raw Referer header.
 *
 * The error branches of MediaController::deleteByToken() and
 * ClientController::getShareXConfig() redirected to the value of the
 * Referer header without checking that it points at the instance.
 */
class OpenRedirectTest extends TestCase
{
    private function createUploader(string $username, ?string $token, int $active = 1): int
    {
        return (int) $this->createUser([
            'email' => $username.'@example.com',
            'username' => $username,
            'password' => password_hash($username.'-pass', PASSWORD_DEFAULT),
            'is_admin' => 0,
            'active' => $active,
            'token' => $token,
            'user_code' => $username,
        ]);
    }

    private function createMedia(int $userId, string $userCode, string $code): int
    {
        $dir = $this->storageRoot().'/'.$userCode;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir.'/'.$code.'.txt', 'fixture');

        $this->database()->query(
            'INSERT INTO `uploads`(`user_id`, `code`, `filename`, `storage_path`, `published`) VALUES (?, ?, ?, ?, 1)',
            [$userId, $code, 'file.txt', $userCode.'/'.$code.'.txt']
        );

        return (int) $this->database()->getPdo()->lastInsertId();
    }

    private function storageRoot(): string
    {
        return rtrim($this->app->getContainer()->get('config')['storage']['path'], '/');
    }

    private function login(string $username): void
    {
        $response = $this->post(route('login'), [
            'username' => $username,
            'password' => $username.'-pass',
        ]);

        $this->assertSame(302, $response->getStatusCode());
    }

    /** @test */
    public function an_unknown_delete_token_does_not_redirect_to_a_foreign_referer()
    {
        $userId = $this->createUploader('victim', 'victim-token');
        $this->createMedia($userId, 'victim', 'media1');

        $response = $this->post(
            route('public.delete', ['userCode' => 'victim', 'mediaCode' => 'media1', 'token' => 'wrong-token']),
            [],
            [],
            ['Referer' => 'http://evil.example/lure']
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('home'), $response->getHeaderLine('Location'));
    }

    /** @test */
    public function a_disabled_owner_does_not_redirect_to_a_foreign_referer()
    {
        $userId = $this->createUploader('disabled', 'disabled-token', 0);
        $this->createMedia($userId, 'disabled', 'media1');

        $response = $this->post(
            route('public.delete', ['userCode' => 'disabled', 'mediaCode' => 'media1', 'token' => 'disabled-token']),
            [],
            [],
            ['Referer' => 'http://evil.example/lure']
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('home'), $response->getHeaderLine('Location'));
    }

    /** @test */
    public function a_missing_referer_falls_back_to_the_home_page()
    {
        $userId = $this->createUploader('victim', 'victim-token');
        $this->createMedia($userId, 'victim', 'media1');

        $response = $this->post(
            route('public.delete', ['userCode' => 'victim', 'mediaCode' => 'media1', 'token' => 'wrong-token'])
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('home'), $response->getHeaderLine('Location'));
    }

    /** @test */
    public function a_same_site_referer_is_reduced_to_its_path()
    {
        $userId = $this->createUploader('victim', 'victim-token');
        $this->createMedia($userId, 'victim', 'media1');

        $response = $this->post(
            route('public.delete', ['userCode' => 'victim', 'mediaCode' => 'media1', 'token' => 'wrong-token']),
            [],
            [],
            ['Referer' => 'http://localhost/home']
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/home', $response->getHeaderLine('Location'));
    }

    /** @test */
    public function a_backslash_in_the_referer_path_does_not_produce_a_protocol_relative_redirect()
    {
        $userId = $this->createUploader('victim', 'victim-token');
        $this->createMedia($userId, 'victim', 'media1');

        $response = $this->post(
            route('public.delete', ['userCode' => 'victim', 'mediaCode' => 'media1', 'token' => 'wrong-token']),
            [],
            [],
            ['Referer' => 'http://localhost/\\evil.example/lure']
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('home'), $response->getHeaderLine('Location'));
    }

    /** @test */
    public function the_sharex_config_page_does_not_redirect_to_a_foreign_referer()
    {
        $userId = $this->createUploader('notoken', null);
        $this->login('notoken');

        $response = $this->get(
            route('config.sharex', ['id' => $userId]),
            [],
            [],
            ['Referer' => 'http://evil.example/lure']
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('home'), $response->getHeaderLine('Location'));
    }
}
