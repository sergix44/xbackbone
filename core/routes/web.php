<?php

use App\Http\Controllers\ResourceController;
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
    Route::livewire('settings', Settings::class)->name('admin.settings')->can('administrate');
    Route::livewire('profile/{tab?}', Profile::class)->name('user.profile')
        ->whereIn('tab', ['profile', 'tokens', 'export', 'delete']);
});

// Route::get('preview/{resource:code}.{ext}', [ResourceController::class, 'preview'])->name('preview.ext');
// Route::get('preview/{resource:code}', [ResourceController::class, 'preview'])->name('preview');
Route::get('raw/{resource:code}.{ext}', [ResourceController::class, 'raw'])->name('raw.ext');
Route::get('raw/{resource:code}', [ResourceController::class, 'raw'])->name('raw');
Route::get('download/{resource:code}.{ext}', [ResourceController::class, 'download'])->name('download.ext');
Route::get('download/{resource:code}', [ResourceController::class, 'download'])->name('download');
Route::get('thumbnail/{resource:code}', [ResourceController::class, 'thumbnail'])->name('thumbnail');
Route::livewire('{resource:code}.{ext}', Preview::class)->name('preview.ext');
Route::livewire('{resource:code}', Preview::class)->name('preview');
