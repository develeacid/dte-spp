<?php

use App\Http\Controllers\Tracking\EvidenciaController;
use App\Livewire\Tracking\CapturaAvance;
use App\Livewire\Tracking\ConcentradoCaptura;
use App\Livewire\Tracking\DashboardIndicadores;
use App\Livewire\Tracking\DetalleIndicador;
use App\Livewire\Tracking\EvidenciaAvance;
use App\Livewire\Tracking\FlujosAvance;
use App\Livewire\Tracking\GestionarDesbloqueos;
use App\Livewire\Tracking\IndicadoresVencidos;
use App\Livewire\Tracking\MisIndicadoresPendientes;
use App\Livewire\Tracking\PanelSeguimiento;
use App\Livewire\Tracking\SabanaCaptura;
use App\Livewire\Tracking\SolicitarDesbloqueo;
use Illuminate\Support\Facades\Route;

Route::prefix('seguimiento')
    ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        Route::get('/', PanelSeguimiento::class)
            ->name('tracking.panel');
        Route::get('/indicador/{indicador}', DetalleIndicador::class)
            ->name('tracking.indicador.detalle');
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
        Route::get('/sabana-captura', SabanaCaptura::class)
            ->name('tracking.sabana-captura')
            ->middleware('can:ver_sabana_captura');
        Route::get('/concentrado-captura', ConcentradoCaptura::class)
            ->name('tracking.concentrado-captura')
            ->middleware('can:ver_concentrado_captura');
        Route::get('/{programa}/dashboard-indicadores', DashboardIndicadores::class)
            ->name('tracking.dashboard-indicadores');
    });
