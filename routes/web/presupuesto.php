<?php

use App\Http\Controllers\Presupuesto\PresupuestalController;
use App\Livewire\Presupuesto;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->prefix('presupuesto')->group(function () {

    // --- Dashboard financiero (lectura) ---
    Route::get('/', Presupuesto\PanelPresupuestal::class)
        ->middleware('can:ver_datos_financieros')
        ->name('presupuesto.panel');

    // --- CRUD Partidas ---
    Route::middleware('can:gestionar_presupuesto')->group(function () {
        Route::get('/partidas', Presupuesto\GestionPartidas::class)
            ->name('presupuesto.partidas');
        Route::get('/partidas/create', Presupuesto\PartidaForm::class)
            ->name('presupuesto.partidas.create');
        Route::get('/partidas/{partida}/edit', Presupuesto\PartidaForm::class)
            ->name('presupuesto.partidas.edit');
        Route::get('/partidas/{partida}/modificaciones', Presupuesto\ModificacionesPartida::class)
            ->name('presupuesto.partidas.modificaciones');
    });

    // --- Captura de Avance Financiero ---
    Route::get('/captura/{programa}', Presupuesto\CapturaAvanceFinanciero::class)
        ->middleware('can:capturar_avance_financiero')
        ->name('presupuesto.captura');

    // --- Importación CSV ---
    Route::get('/importar', Presupuesto\ImportarPresupuesto::class)
        ->middleware('can:gestionar_presupuesto')
        ->name('presupuesto.importar');

    // --- Programa Operativo Anual (vista derivada vw_poa) ---
    Route::get('/poa', Presupuesto\ReportePoa::class)
        ->middleware('can:ver_datos_financieros')
        ->name('presupuesto.poa');

    // --- Reportes y Exportación ---
    Route::middleware('can:exportar_cuenta_publica')->group(function () {
        Route::get('/cuenta-publica', Presupuesto\CuentaPublicaView::class)
            ->name('presupuesto.cuenta-publica');
        Route::get('/exportar/pdf/{ejercicio}', [PresupuestalController::class, 'exportarPdf'])
            ->name('presupuesto.exportar.pdf');
        Route::get('/exportar/excel/{ejercicio}', [PresupuestalController::class, 'exportarExcel'])
            ->name('presupuesto.exportar.excel');
    });
});
