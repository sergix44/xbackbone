<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\ResourceController;
use App\Http\Middleware\EnsureResourceAccessible;
use App\Livewire\Admin\Settings;
use App\Livewire\Dashboard;
use App\Livewire\Integrations;
use App\Livewire\Preview;
use App\Livewire\User\Profile;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/dashboard');

Route::group(['middleware' => ['auth', 'verified']], static function () {
    Route::livewire('dashboard', Dashboard::class)->name('dashboard');
    Route::livewire('integrations', Integrations::class)->name('integrations');
    Route::get('integrations/sharex', [IntegrationController::class, 'shareX'])->name('integrations.sharex');
    Route::livewire('settings/{tab?}', Settings::class)->name('admin.settings')
        ->whereIn('tab', ['general', 'users', 'statistics'])
        ->can('administrate');
    Route::get('profile/export/download', [ExportController::class, 'download'])->name('user.profile.export');
    Route::livewire('profile/{tab?}', Profile::class)->name('user.profile')
        ->whereIn('tab', ['profile', 'tokens', 'passkeys', 'export', 'delete']);
});

Route::get('delete/{resource:code}', [ResourceController::class, 'delete'])->name('resource.delete')->middleware('signed');

Route::middleware(EnsureResourceAccessible::class)->group(static function () {
    Route::get('raw/{resource:code}.{ext}', [ResourceController::class, 'raw'])->name('raw.ext');
    Route::get('raw/{resource:code}', [ResourceController::class, 'raw'])->name('raw');
    Route::get('download/{resource:code}.{ext}', [ResourceController::class, 'download'])->name('download.ext');
    Route::get('download/{resource:code}', [ResourceController::class, 'download'])->name('download');
    Route::get('thumbnail/{resource:code}', [ResourceController::class, 'thumbnail'])->name('thumbnail');
    Route::livewire('{resource:code}.{ext}', Preview::class)->name('preview.ext');
    Route::livewire('{resource:code}', Preview::class)->name('preview');
});
