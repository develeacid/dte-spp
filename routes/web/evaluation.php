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

        Route::get('/mir-publica/{id}', function (\Illuminate\Http\Request $request, int $id) {
            $programa = \App\Models\ProgramaPresupuestario::findOrFail($id);
            $contenido = (new \App\Exports\Pdf\MirPdfExport(
                $programa,
                (int) $request->input('ejercicio_fiscal', date('Y')),
            ))->generate();

            $filename = "mir-{$programa->clave}-" . now()->format('Ymd') . '.pdf';

            return new \Illuminate\Http\Response($contenido, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
            ]);
        })->name('evaluation.mir-publica');

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
                Route::get('/anexo-11/{programa}', \App\Http\Controllers\Evaluation\Anexo11Controller::class)
                    ->name('evaluation.anexo-11');
            });

        Route::middleware('can:exportar_reportes')
            ->prefix('datos-abiertos')
            ->group(function () {
                Route::get('/csv/{ejercicio}', [\App\Http\Controllers\Evaluation\DatosAbiertosController::class, 'csv'])
                    ->name('evaluation.datos-abiertos.csv');
                Route::get('/json/{ejercicio}', [\App\Http\Controllers\Evaluation\DatosAbiertosController::class, 'json'])
                    ->name('evaluation.datos-abiertos.json');
                Route::get('/diccionario', [\App\Http\Controllers\Evaluation\DatosAbiertosController::class, 'diccionario'])
                    ->name('evaluation.datos-abiertos.diccionario');
                Route::get('/zip/{ejercicio}', [\App\Http\Controllers\Evaluation\DatosAbiertosController::class, 'zip'])
                    ->name('evaluation.datos-abiertos.zip');
            });
    });
