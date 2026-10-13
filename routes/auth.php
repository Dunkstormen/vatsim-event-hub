<?php

use App\Http\Controllers\Auth\VatsimAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('auth/vatsim/redirect', [VatsimAuthController::class, 'redirect'])->name('auth.vatsim.redirect');
    Route::get('auth/vatsim/callback', [VatsimAuthController::class, 'callback'])->name('auth.vatsim.callback');
});
