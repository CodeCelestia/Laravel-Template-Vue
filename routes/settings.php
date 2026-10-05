<?php

use App\Http\Controllers\Settings\BrandingController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    Route::get('settings/branding', [BrandingController::class, 'edit'])->name('branding.edit');
    Route::patch('settings/branding', [BrandingController::class, 'update'])->name('branding.update');
    Route::post('settings/branding/logo', [BrandingController::class, 'updateLogo'])->name('branding.logo.update');
    Route::delete('settings/branding/logo', [BrandingController::class, 'resetLogo'])->name('branding.logo.destroy');
    Route::post('settings/branding/favicon', [BrandingController::class, 'updateFavicon'])->name('branding.favicon.update');
    Route::delete('settings/branding/favicon', [BrandingController::class, 'resetFavicon'])->name('branding.favicon.destroy');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
