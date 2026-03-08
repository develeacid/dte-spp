<?php

use App\Livewire\Tracking\IndicadoresVencidos;
use App\Livewire\Tracking\MisIndicadoresPendientes;
use Illuminate\Support\Facades\Route;

Route::prefix('seguimiento')
    ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        Route::get('/pendientes', MisIndicadoresPendientes::class)
            ->name('tracking.pendientes');
        Route::get('/vencidos', IndicadoresVencidos::class)
            ->name('tracking.vencidos');
    });
