<?php


namespace Tests\Feature\Media;

use Tests\TestCase;

class VanityOwnershipTest extends TestCase
{
    private function createUpload(int $userId, string $code): int
    {
        $this->database()->query(
            'INSERT INTO `uploads`(`user_id`, `code`, `filename`, `storage_path`, `published`) VALUES (?, ?, ?, ?, 1)',
            [$userId, $code, 'file.txt', 'test/file.txt']
        );

        return (int) $this->database()->getPdo()->lastInsertId();
    }

    private function login(string $username, string $password): void
    {
        $response = $this->post(route('login'), ['username' => $username, 'password' => $password]);
        $this->assertSame(302, $response->getStatusCode());
    }

    private function codeOf(int $mediaId): string
    {
        return $this->database()->query('SELECT `code` FROM `uploads` WHERE `id` = ?', [$mediaId])->fetch()->code;
    }

    /** @test */
    public function a_user_cannot_change_the_vanity_of_another_users_upload()
    {
        $victimId = $this->createUser([
            'email' => 'victim@example.com',
            'username' => 'victim',
            'password' => password_hash('victim-pass', PASSWORD_DEFAULT),
            'is_admin' => 0,
        ]);
        $this->createUser([
            'email' => 'attacker@example.com',
            'username' => 'attacker',
            'password' => password_hash('attacker-pass', PASSWORD_DEFAULT),
            'is_admin' => 0,
        ]);
        $mediaId = $this->createUpload((int) $victimId, 'victimcode');

        $this->login('attacker', 'attacker-pass');

        $response = $this->post(route('upload.vanity', ['id' => $mediaId]), ['vanity' => 'stolen']);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('victimcode', $this->codeOf($mediaId));
    }

    /** @test */
    public function a_user_can_change_the_vanity_of_their_own_upload()
    {
        $userId = $this->createUser([
            'email' => 'owner@example.com',
            'username' => 'owner',
            'password' => password_hash('owner-pass', PASSWORD_DEFAULT),
            'is_admin' => 0,
        ]);
        $mediaId = $this->createUpload((int) $userId, 'owncode');

        $this->login('owner', 'owner-pass');

        $response = $this->post(route('upload.vanity', ['id' => $mediaId]), ['vanity' => 'newcode']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('newcode', $this->codeOf($mediaId));
    }

    /** @test */
    public function an_admin_can_change_the_vanity_of_another_users_upload()
    {
        $victimId = $this->createUser([
            'email' => 'victim@example.com',
            'username' => 'victim',
            'password' => password_hash('victim-pass', PASSWORD_DEFAULT),
            'is_admin' => 0,
        ]);
        $this->createAdminUser('admin@example.com', 'admin', 'admin-pass');
        $mediaId = $this->createUpload((int) $victimId, 'victimcode');

        $this->login('admin', 'admin-pass');

        $response = $this->post(route('upload.vanity', ['id' => $mediaId]), ['vanity' => 'admincode']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('admincode', $this->codeOf($mediaId));
    }
}
