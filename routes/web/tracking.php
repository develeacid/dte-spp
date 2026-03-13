<?php

use App\Http\Controllers\Tracking\EvidenciaController;
use App\Livewire\Tracking\CapturaAvance;
use App\Livewire\Tracking\EvidenciaAvance;
use App\Livewire\Tracking\FlujosAvance;
use App\Livewire\Tracking\GestionarDesbloqueos;
use App\Livewire\Tracking\IndicadoresVencidos;
use App\Livewire\Tracking\MisIndicadoresPendientes;
use App\Livewire\Tracking\SolicitarDesbloqueo;
use Illuminate\Support\Facades\Route;

Route::prefix('seguimiento')
    ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        Route::get('/', \App\Livewire\Tracking\PanelSeguimiento::class)
            ->name('tracking.panel');
        Route::get('/pendientes', MisIndicadoresPendientes::class)
            ->name('tracking.pendientes');
        Route::get('/vencidos', IndicadoresVencidos::class)
            ->name('tracking.vencidos');
        Route::get('/captura/{avance}', CapturaAvance::class)
            ->name('tracking.captura');
        Route::get('/avance/{avance}/evidencias', EvidenciaAvance::class)
            ->name('tracking.evidencia.index');
        Route::get('/evidencia/{evidencia}/download', [EvidenciaController::class, 'download'])
            ->name('tracking.evidencia.download');
        Route::get('/flujo/{avance}', FlujosAvance::class)
            ->name('tracking.flujo');
        Route::get('/desbloqueo/{avance}', SolicitarDesbloqueo::class)
            ->name('tracking.desbloqueo.solicitar');
        Route::get('/desbloqueos', GestionarDesbloqueos::class)
            ->name('tracking.desbloqueos');
        Route::get('/sabana-captura', \App\Livewire\Tracking\SabanaCaptura::class)
            ->name('tracking.sabana-captura')
            ->middleware('can:ver_sabana_captura');
    });
