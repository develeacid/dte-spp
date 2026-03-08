<?php

use Illuminate\Support\Facades\Route;

Route::prefix('evaluacion')
    ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        Route::get('/programa/{evaluacion}', \App\Livewire\Evaluation\EvaluacionProgramaView::class)
            ->name('evaluation.programa');

        Route::get('/transversal', \App\Livewire\Evaluation\PanelTransversal::class)
            ->name('evaluation.transversal')
            ->middleware('can:exportar_reportes');

        Route::middleware('can:exportar_reportes')
            ->prefix('exportar')
            ->group(function () {
                Route::get('/pdf/{tipo}/{id?}', [\App\Http\Controllers\Evaluation\ExportController::class, 'pdf'])
                    ->name('evaluation.exportar.pdf');
                Route::get('/excel/{tipo}/{id?}', [\App\Http\Controllers\Evaluation\ExportController::class, 'excel'])
                    ->name('evaluation.exportar.excel');
                Route::post('/async/{formato}/{tipo}', [\App\Http\Controllers\Evaluation\ExportController::class, 'async'])
                    ->name('evaluation.exportar.async');
                Route::get('/descargar/{filename}', [\App\Http\Controllers\Evaluation\ExportController::class, 'descargar'])
                    ->name('evaluation.exportar.descargar');
            });
    });
