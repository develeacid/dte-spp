<?php

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

require __DIR__ . '/web/cascade.php';
require __DIR__ . '/web/mml.php';
require __DIR__ . '/web/tracking.php';
