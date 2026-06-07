<?php

use App\Exports\Pdf\MirPdfExport;
use App\Http\Controllers\Evaluation\Anexo11Controller;
use App\Http\Controllers\Evaluation\AsmController;
use App\Http\Controllers\Evaluation\AsmXlsxExportController;
use App\Http\Controllers\Evaluation\DatosAbiertosController;
use App\Http\Controllers\Evaluation\ExportController;
use App\Http\Controllers\Evaluation\PadronShcpController;
use App\Http\Controllers\Evaluation\PresupuestoCapituloXlsxController;
use App\Livewire\Evaluation\AcumuladoAnual;
use App\Livewire\Evaluation\AsmForm;
use App\Livewire\Evaluation\AsmIndex;
use App\Livewire\Evaluation\AsmShow;
use App\Livewire\Evaluation\EvaluacionExternaForm;
use App\Livewire\Evaluation\EvaluacionExternaIndex;
use App\Livewire\Evaluation\EvaluacionProgramaView;
use App\Livewire\Evaluation\PanelTransversal;
use App\Livewire\Evaluation\ReporteDesviaciones;
use App\Models\ProgramaPresupuestario;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::prefix('evaluacion')
    ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        Route::get('/programa/{evaluacion}', EvaluacionProgramaView::class)
            ->name('evaluation.programa');

        Route::get('/transversal', PanelTransversal::class)
            ->name('evaluation.transversal')
            ->middleware('can:exportar_reportes');

        Route::middleware('can:exportar_reportes')
            ->get('/desviaciones', ReporteDesviaciones::class)
            ->name('evaluation.desviaciones');

        Route::middleware('can:exportar_reportes')
            ->get('/acumulado-anual', AcumuladoAnual::class)
            ->name('evaluation.acumulado-anual');

        Route::get('/mir-publica/{id}', function (Request $request, int $id) {
            $programa = ProgramaPresupuestario::findOrFail($id);
            $contenido = (new MirPdfExport(
                $programa,
                (int) $request->input('ejercicio_fiscal', date('Y')),
            ))->generate();

            $filename = "mir-{$programa->clave}-".now()->format('Ymd').'.pdf';

            return new Response($contenido, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
            ]);
        })->name('evaluation.mir-publica');

        Route::middleware('can:exportar_reportes')
            ->prefix('exportar')
            ->group(function () {
                Route::get('/pdf/{tipo}/{id?}', [ExportController::class, 'pdf'])
                    ->name('evaluation.exportar.pdf');
                Route::get('/excel/{tipo}/{id?}', [ExportController::class, 'excel'])
                    ->name('evaluation.exportar.excel');
                Route::post('/async/{formato}/{tipo}', [ExportController::class, 'async'])
                    ->name('evaluation.exportar.async');
                Route::get('/descargar/{filename}', [ExportController::class, 'descargar'])
                    ->name('evaluation.exportar.descargar');
                Route::get('/anexo-11/{programa}', Anexo11Controller::class)
                    ->name('evaluation.anexo-11');
            });

        Route::middleware('can:exportar_padron_shcp')
            ->get('/exportar/padron-shcp/{programa}', PadronShcpController::class)
            ->name('evaluation.padron-shcp');

        Route::middleware('can:exportar_cuenta_publica')
            ->get('/exportar/presupuesto-capitulo/{programa}',
                [PresupuestoCapituloXlsxController::class, 'download'])
            ->name('evaluation.exportar.presupuesto-capitulo');

        Route::prefix('asms')->name('evaluation.asms.')->group(function () {
            Route::get('/', AsmIndex::class)
                ->middleware('can:ver_asm')
                ->name('index');

            Route::get('/crear', AsmForm::class)
                ->middleware('can:gestionar_asm')
                ->name('create');

            Route::get('/{asm}/editar', AsmForm::class)
                ->middleware('can:gestionar_asm')
                ->name('edit');

            Route::delete('/{asm}', [AsmController::class, 'destroy'])
                ->middleware('can:gestionar_asm')
                ->name('destroy');

            Route::get('/exportar/xlsx', [AsmXlsxExportController::class, 'download'])
                ->middleware('can:exportar_reportes')
                ->name('export.xlsx');

            Route::get('/{asm}', AsmShow::class)
                ->middleware('can:ver_asm')
                ->name('show');
        });

        Route::prefix('externas')->name('evaluation.externas.')->group(function () {
            Route::get('/', EvaluacionExternaIndex::class)
                ->middleware('can:ver_evaluacion_externa')
                ->name('index');

            Route::get('/crear', EvaluacionExternaForm::class)
                ->middleware('can:gestionar_evaluacion_externa')
                ->name('create');

            Route::get('/{evaluacionExterna}/editar', EvaluacionExternaForm::class)
                ->middleware('can:gestionar_evaluacion_externa')
                ->name('edit');

            // Ruta show: Task 4
        });

        Route::middleware('can:exportar_reportes')
            ->prefix('datos-abiertos')
            ->group(function () {
                Route::get('/csv/{ejercicio}', [DatosAbiertosController::class, 'csv'])
                    ->name('evaluation.datos-abiertos.csv');
                Route::get('/json/{ejercicio}', [DatosAbiertosController::class, 'json'])
                    ->name('evaluation.datos-abiertos.json');
                Route::get('/diccionario', [DatosAbiertosController::class, 'diccionario'])
                    ->name('evaluation.datos-abiertos.diccionario');
                Route::get('/zip/{ejercicio}', [DatosAbiertosController::class, 'zip'])
                    ->name('evaluation.datos-abiertos.zip');
            });
    });
