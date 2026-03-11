<?php

use App\Livewire\Mml\AlineacionEstrategica;
use App\Livewire\Mml\EmbudoPoblaciones;
use Illuminate\Support\Facades\Route;

Route::prefix('mml')
    ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {

        // Lista de programas
        Route::get('/programas', \App\Livewire\Mml\ListaProgramas::class)
            ->name('mml.programas');

        // Flujo de importación MIR
        Route::prefix('importar')->group(function () {
            Route::get('/', \App\Livewire\Mml\DashboardImportaciones::class)
                ->name('mml.importaciones');

            Route::get('/nuevo', \App\Livewire\Mml\ImportarPrograma::class)
                ->name('mml.importar.nuevo');

            Route::get('/{importacion}/completar', \App\Livewire\Mml\CompletarHuecos::class)
                ->name('mml.importar.completar');

            Route::get('/{importacion}/vincular', \App\Livewire\Mml\VincularAlineacion::class)
                ->name('mml.importar.vincular');

            Route::get('/{importacion}/calendarizar', \App\Livewire\Mml\CalendarizarMetas::class)
                ->name('mml.importar.calendarizar');
        });

        // Etapas del MML para un programa
        Route::prefix('{programa}')
            ->group(function () {
                Route::get('/etapa/1', \App\Livewire\Mml\DefinicionProblema::class)
                    ->name('mml.etapa1');
                Route::get('/etapa/2', \App\Livewire\Mml\ArbolProblemaBuilder::class)
                    ->name('mml.etapa2');
                Route::get('/etapa/3', \App\Livewire\Mml\ArbolObjetivosBuilder::class)
                    ->name('mml.etapa3');
                Route::get('/etapa/4', \App\Livewire\Mml\SeleccionAlternativas::class)
                    ->name('mml.etapa4');
                Route::get('/etapa/5', EmbudoPoblaciones::class)
                    ->name('mml.etapa5');
                Route::get('/etapa/6', AlineacionEstrategica::class)
                    ->name('mml.etapa6');
                Route::get('/etapa/7/mir', \App\Livewire\Mml\MirEditor::class)
                    ->name('mml.mir');
            });
    });
