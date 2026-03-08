<?php

use Illuminate\Support\Facades\Route;

Route::prefix('mml')
    ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {

        // Etapas del MML para un programa
        Route::prefix('{programa}')
            ->group(function () {
                Route::get('/etapa/1', \App\Livewire\Mml\DefinicionProblema::class)
                    ->name('mml.etapa1');
                Route::get('/etapa/2', \App\Livewire\Mml\ArbolProblemaBuilder::class)
                    ->name('mml.etapa2');
            });
    });
