<?php

use App\Livewire\Admin\MonitoreoIa;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/branding', function () {
    return view('branding.index');
})->name('branding.index');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Admin routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/admin/monitoreo-ia', MonitoreoIa::class)
        ->name('admin.monitoreo-ia')
        ->middleware('can:administrar_usuarios');
});

require __DIR__ . '/web/cascade.php';
require __DIR__ . '/web/mml.php';
require __DIR__ . '/web/tracking.php';
require __DIR__ . '/web/evaluation.php';
