<?php

use App\Controllers\AdminController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\PasswordRecoveryController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;
use App\Controllers\ExportController;
use App\Controllers\MediaController;
use App\Controllers\ProfileController;
use App\Controllers\SettingController;
use App\Controllers\TagController;
use App\Controllers\UpgradeController;
use App\Controllers\UploadController;
use App\Controllers\UserController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CheckForMaintenanceMiddleware;
use Slim\Routing\RouteCollectorProxy;

global $app;
$app->group('', function (RouteCollectorProxy $group) {
    $group->get('/home[/page/{page}]', [DashboardController::class, 'home'])->setName('home');
    $group->get('/upload', [UploadController::class, 'uploadWebPage'])->setName('upload.web.show');
    $group->post('/upload/web', [UploadController::class, 'uploadWeb'])->setName('upload.web');
    $group->get('/home/switchView', [DashboardController::class, 'switchView'])->setName('switchView');

    $group->group('', function (RouteCollectorProxy $group) {
        $group->get('/system/deleteOrphanFiles', [AdminController::class, 'deleteOrphanFiles'])->setName('system.deleteOrphanFiles');
        $group->get('/system/recalculateUserQuota', [AdminController::class, 'recalculateUserQuota'])->setName('system.recalculateUserQuota');

        $group->get('/system/themes', [AdminController::class, 'getThemes'])->setName('theme');

        $group->post('/system/settings/save', [SettingController::class, 'saveSettings'])->setName('settings.save');

        $group->post('/system/upgrade', [UpgradeController::class, 'upgrade'])->setName('system.upgrade');
        $group->get('/system/checkForUpdates', [UpgradeController::class, 'checkForUpdates'])->setName('system.checkForUpdates');
        $group->get('/system/changelog', [UpgradeController::class, 'changelog'])->setName('system.changelog');

        $group->get('/system', [AdminController::class, 'system'])->setName('system');

        $group->get('/users[/page/{page}]', [UserController::class, 'index'])->setName('user.index');
    })->add(AdminMiddleware::class);

    $group->group('/user', function (RouteCollectorProxy $group) {
        $group->get('/create', [UserController::class, 'create'])->setName('user.create');
        $group->post('/create', [UserController::class, 'store'])->setName('user.store');
        $group->get('/{id}/edit', [UserController::class, 'edit'])->setName('user.edit');
        $group->post('/{id}', [UserController::class, 'update'])->setName('user.update');
        $group->get('/{id}/delete', [UserController::class, 'delete'])->setName('user.delete');
        $group->get('/{id}/clear', [UserController::class, 'clearUserMedia'])->setName('user.clear');
    })->add(AdminMiddleware::class);

    $group->get('/profile', [ProfileController::class, 'profile'])->setName('profile');
    $group->post('/profile/{id}', [ProfileController::class, 'profileEdit'])->setName('profile.update');
    $group->post('/user/{id}/refreshToken', [UserController::class, 'refreshToken'])->setName('refreshToken');
    $group->get('/user/{id}/config/sharex', [ClientController::class, 'getShareXConfig'])->setName('config.sharex');
    $group->get('/user/{id}/config/windows', [ClientController::class, 'getWindowsConfig'])->setName('config.windows');
    $group->get('/user/{id}/config/script', [ClientController::class, 'getBashScript'])->setName('config.script');
    $group->get('/user/{id}/config/kde_script', [ClientController::class, 'getKDEScript'])->setName('kde_config.script');

    $group->get('/user/{id}/export', [ExportController::class, 'downloadData'])->setName('export.data');

    $group->post('/upload/{id}/publish', [MediaController::class, 'togglePublish'])->setName('upload.publish');
    $group->post('/upload/{id}/unpublish', [MediaController::class, 'togglePublish'])->setName('upload.unpublish');
    $group->post('/upload/{id}/vanity', [MediaController::class, 'createVanity'])->setName('upload.vanity');
    $group->get('/upload/{id}/raw', [MediaController::class, 'getRawById'])->add(AdminMiddleware::class)->setName('upload.raw');
    $group->map(['GET', 'POST'], '/upload/{id}/delete', [MediaController::class, 'delete'])->setName('upload.delete');

    $group->post('/tag/add', [TagController::class, 'addTag'])->setName('tag.add');
    $group->post('/tag/remove', [TagController::class, 'removeTag'])->setName('tag.remove');
})->add(App\Middleware\CheckForMaintenanceMiddleware::class)->add(AuthMiddleware::class);

$app->get('/', [DashboardController::class, 'redirects'])->setName('root');
$app->get('/register', [RegisterController::class, 'registerForm'])->setName('register.show');
$app->post('/register', [RegisterController::class, 'register'])->setName('register');
$app->get('/activate/{activateToken}', [RegisterController::class, 'activateUser'])->setName('activate');
$app->get('/recover', [PasswordRecoveryController::class, 'recover'])->setName('recover');
$app->post('/recover/mail', [PasswordRecoveryController::class, 'recoverMail'])->setName('recover.mail');
$app->get('/recover/password/{resetToken}', [PasswordRecoveryController::class, 'recoverPasswordForm'])->setName('recover.password.view');
$app->post('/recover/password/{resetToken}', [PasswordRecoveryController::class, 'recoverPassword'])->setName('recover.password');
$app->get('/login', [LoginController::class, 'show'])->setName('login.show');
$app->post('/login', [LoginController::class, 'login'])->setName('login');
$app->map(['GET', 'POST'], '/logout', [LoginController::class, 'logout'])->setName('logout');

$app->post('/upload', [UploadController::class, 'uploadEndpoint'])->setName('upload');

$app->get('/user/{token}/config/screencloud', [ClientController::class, 'getScreenCloudConfig'])->setName('config.screencloud')->add(CheckForMaintenanceMiddleware::class);
$app->get('/{userCode}/{mediaCode}', [MediaController::class, 'show'])->setName('public');
$app->get('/{userCode}/{mediaCode}/delete/{token}', [MediaController::class, 'show'])->setName('public.delete.show')->add(CheckForMaintenanceMiddleware::class);
$app->post('/{userCode}/{mediaCode}/delete/{token}', [MediaController::class, 'deleteByToken'])->setName('public.delete')->add(CheckForMaintenanceMiddleware::class);
$app->get('/{userCode}/{mediaCode}/raw[.{ext}]', [MediaController::class, 'getRaw'])->setName('public.raw');
$app->get('/{userCode}/{mediaCode}/download', [MediaController::class, 'download'])->setName('public.download');
