<?php

use App\Http\Controllers\Api\V1\DeleteController;
use App\Http\Controllers\Api\V1\UploadController;

Route::post('upload', UploadController::class)->name('upload');
Route::delete('resources/{resource:code}', DeleteController::class)->name('resources.destroy');
