<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/branding', function () {
    return view('branding.index');
})->name('branding.index');

// Onboarding — activación de cuenta (sin auth, usa token)
Route::prefix('activar')->name('activar.')->group(function () {
    Route::get('/{token}', [\App\Http\Controllers\OnboardingController::class, 'showSetPassword'])->name('show');
    Route::post('/{token}/password', [\App\Http\Controllers\OnboardingController::class, 'storePassword'])->name('password');
    Route::get('/{token}/2fa', [\App\Http\Controllers\OnboardingController::class, 'showSetup2fa'])->name('2fa');
    Route::post('/{token}/2fa', [\App\Http\Controllers\OnboardingController::class, 'confirm2fa'])->name('2fa.confirm');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', \App\Livewire\Dashboard::class)->name('dashboard');
});

require __DIR__ . '/web/admin.php';
require __DIR__ . '/web/cascade.php';
require __DIR__ . '/web/mml.php';
require __DIR__ . '/web/tracking.php';
require __DIR__ . '/web/evaluation.php';
