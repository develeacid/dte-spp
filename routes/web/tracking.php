<?php

use App\Http\Controllers\Tracking\EvidenciaController;
use App\Livewire\Tracking\EvidenciaAvance;
use App\Livewire\Tracking\IndicadoresVencidos;
use App\Livewire\Tracking\MisIndicadoresPendientes;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/tracking/avance/{avance}/evidencias', EvidenciaAvance::class)
        ->name('tracking.avance.evidencias');

    Route::get('/tracking/evidencia/{evidencia}/download', [EvidenciaController::class, 'download'])
        ->name('tracking.evidencia.download');

    Route::prefix('seguimiento')->group(function () {
        Route::get('/pendientes', MisIndicadoresPendientes::class)
            ->name('tracking.pendientes');
        Route::get('/vencidos', IndicadoresVencidos::class)
            ->name('tracking.vencidos');
    });
});
