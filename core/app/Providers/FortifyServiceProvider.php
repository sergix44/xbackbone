<?php

namespace XBB\Providers;

use XBB\Actions\Fortify\CreateNewUser;
use XBB\Actions\Fortify\ResetUserPassword;
use XBB\Actions\Fortify\UpdateUserPassword;
use XBB\Actions\Fortify\UpdateUserProfileInformation;
use XBB\Livewire\Auth\ConfirmPassword;
use XBB\Livewire\Auth\ForgotPassword;
use XBB\Livewire\Auth\Login;
use XBB\Livewire\Auth\Register;
use XBB\Livewire\Auth\ResetPassword;
use XBB\Livewire\Auth\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', static function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', static function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        Fortify::loginView(static function () {
            return app()->call(Login::class);
        });

        Fortify::registerView(static function () {
            return app()->call(Register::class);
        });

        Fortify::requestPasswordResetLinkView(static function () {
            return app()->call(ForgotPassword::class);
        });

        Fortify::resetPasswordView(static function () {
            return app()->call(ResetPassword::class);
        });

        Fortify::verifyEmailView(static function () {
            return app()->call(VerifyEmail::class);
        });

        Fortify::confirmPasswordView(static function () {
            return app()->call(ConfirmPassword::class);
        });
    }
}
