<?php

use App\Http\Controllers\AyudaController;
use App\Http\Controllers\OnboardingController;
use App\Livewire\Dashboard;
use App\Livewire\NotificationsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Onboarding — activación de cuenta (sin auth, usa token)
Route::prefix('activar')->name('activar.')->group(function () {
    Route::get('/{token}', [OnboardingController::class, 'showSetPassword'])->name('show');
    Route::post('/{token}/password', [OnboardingController::class, 'storePassword'])->name('password');
    Route::get('/{token}/2fa', [OnboardingController::class, 'showSetup2fa'])->name('2fa');
    Route::post('/{token}/2fa', [OnboardingController::class, 'confirm2fa'])->name('2fa.confirm');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/notifications', NotificationsIndex::class)->name('notifications.index');
    Route::get('/ayuda', AyudaController::class)->name('ayuda');
});

require __DIR__.'/web/admin.php';
require __DIR__.'/web/cascade.php';
require __DIR__.'/web/mml.php';
require __DIR__.'/web/tracking.php';
require __DIR__.'/web/evaluation.php';
require __DIR__.'/web/presupuesto.php';
require __DIR__.'/web/juridico.php';
require __DIR__.'/web/transparencia.php';
require __DIR__.'/web/portal.php';
