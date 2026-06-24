<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\OembedController;
use App\Http\Controllers\ResourceController;
use App\Http\Middleware\EnsureResourceAccessible;
use App\Http\Middleware\ServeSocialEmbed;
use App\Livewire\Admin\Settings;
use App\Livewire\Dashboard;
use App\Livewire\Integrations;
use App\Livewire\Preview;
use App\Livewire\User\Profile;
use App\Models\Resource;
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

// Registered before the single-segment preview route below so it is not captured as a resource code.
Route::get('oembed', [OembedController::class, 'show'])->name('oembed');

Route::middleware([EnsureResourceAccessible::class, ServeSocialEmbed::class])->group(static function () {
    Route::get('raw/{resource:code}.{ext}', [ResourceController::class, 'raw'])->name('raw.ext');
    Route::get('raw/{resource:code}', [ResourceController::class, 'raw'])->name('raw');
    Route::get('download/{resource:code}.{ext}', [ResourceController::class, 'download'])->name('download.ext');
    Route::get('download/{resource:code}', [ResourceController::class, 'download'])->name('download');
    Route::get('thumbnail/{resource:code}', [ResourceController::class, 'thumbnail'])->name('thumbnail');
    Route::livewire('{resource:code}.{ext}', Preview::class)->name('preview.ext');
    Route::livewire('{resource:code}', Preview::class)->name('preview');
});

/*
 * Legacy URL fallback. Old XBackBone links were /{userCode}/{code}[.ext]; resources imported from a
 * legacy instance keep their original code in `legacy_code`. This two-segment route is registered last
 * so it only catches old links the current single-segment routes above cannot, and permanently
 * redirects them to the current /{code} URL.
 */
Route::get('{legacyUserCode}/{legacyCode}', static function (string $legacyUserCode, string $legacyCode) {
    $resource = Resource::query()
        ->where('legacy_code', pathinfo($legacyCode, PATHINFO_FILENAME))
        ->firstOrFail();

    return redirect()->route('preview', ['resource' => $resource->code], 301);
})->name('legacy.redirect');
