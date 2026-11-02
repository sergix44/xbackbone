<?php


namespace Tests\Feature\Auth;

use Tests\Support\RecaptchaResponseStream;
use Tests\TestCase;

/**
 * The reCAPTCHA verification of XBackBone was implemented with a wrong
 * boolean expression: the failure of the verification was checked only when
 * the verification itself had SUCCEEDED ("if ($recaptcha->success &&
 * $recaptcha->score < 0.5)"). A failed verification was therefore treated as
 * a successful one and the captcha was effectively disabled. The check also
 * failed open when the verification endpoint could not be reached.
 */
class RecaptchaVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->updateSetting('recaptcha_enabled', 'on');
        $this->updateSetting('recaptcha_secret_key', 'test-secret-key');
        $this->updateSetting('recaptcha_site_key', 'test-site-key');

        // The answer of the verification endpoint is faked locally: no request
        // ever leaves the machine.
        RecaptchaResponseStream::$body = '{"success":false,"error-codes":["invalid-input-response"]}';
        stream_wrapper_unregister('https');
        stream_wrapper_register('https', RecaptchaResponseStream::class);
    }

    protected function tearDown(): void
    {
        stream_wrapper_restore('https');

        parent::tearDown();
    }

    /**
     * A verification that failed must block the login.
     *
     * @test
     */
    public function a_login_is_refused_when_the_recaptcha_verification_fails()
    {
        $this->createUser([
            'email' => 'user@example.com',
            'username' => 'user',
            'password' => password_hash('user', PASSWORD_DEFAULT),
            'is_admin' => 0,
        ]);

        $response = $this->post(route('login'), [
            'username' => 'user',
            'password' => 'user',
            'recaptcha_token' => 'a-forged-token',
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->getHeaderLine('Location'));
        $this->assertFalse($_SESSION['logged'] ?? false, 'The user must not be logged in when the captcha verification fails.');
    }

    /**
     * An unreachable or unparsable answer of the verification endpoint must
     * block the login as well (the check must fail closed).
     *
     * @test
     */
    public function a_login_is_refused_when_the_recaptcha_verification_is_unreachable()
    {
        RecaptchaResponseStream::$body = '';

        $this->createUser([
            'email' => 'user@example.com',
            'username' => 'user',
            'password' => password_hash('user', PASSWORD_DEFAULT),
            'is_admin' => 0,
        ]);

        $response = $this->post(route('login'), [
            'username' => 'user',
            'password' => 'user',
            'recaptcha_token' => 'a-forged-token',
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->getHeaderLine('Location'));
        $this->assertFalse($_SESSION['logged'] ?? false, 'The user must not be logged in when the captcha verification cannot be performed.');
    }

    /**
     * A verification that succeeded but with a score that is too low must
     * block the login as well.
     *
     * @test
     */
    public function a_login_is_refused_when_the_recaptcha_score_is_too_low()
    {
        RecaptchaResponseStream::$body = '{"success":true,"score":0.1}';

        $this->createUser([
            'email' => 'user@example.com',
            'username' => 'user',
            'password' => password_hash('user', PASSWORD_DEFAULT),
            'is_admin' => 0,
        ]);

        $response = $this->post(route('login'), [
            'username' => 'user',
            'password' => 'user',
            'recaptcha_token' => 'a-low-scoring-token',
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->getHeaderLine('Location'));
        $this->assertFalse($_SESSION['logged'] ?? false, 'The user must not be logged in when the captcha score is too low.');
    }

    /**
     * A valid verification must still let the user in.
     *
     * @test
     */
    public function a_login_is_accepted_when_the_recaptcha_verification_succeeds()
    {
        RecaptchaResponseStream::$body = '{"success":true,"score":0.9}';

        $this->createUser([
            'email' => 'user@example.com',
            'username' => 'user',
            'password' => password_hash('user', PASSWORD_DEFAULT),
            'is_admin' => 0,
        ]);

        $response = $this->post(route('login'), [
            'username' => 'user',
            'password' => 'user',
            'recaptcha_token' => 'a-valid-token',
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('home'), $response->getHeaderLine('Location'));
        $this->assertTrue($_SESSION['logged'] ?? false, 'The user must be logged in when the captcha verification succeeds.');
    }
}
