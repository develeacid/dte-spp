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
    });
