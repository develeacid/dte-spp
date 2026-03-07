<?php

use App\Http\Controllers\Cascade\PedController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'permission:gestionar_catalogos'
])->prefix('cascade')->name('cascade.')->group(function () {

    Route::prefix('ped')->name('ped.')->group(function () {
        Route::get('/', [PedController::class, 'index'])->name('index');

        // Páginas de formulario (GET) — Plan
        Route::get('/plan/create', [PedController::class, 'createPlan'])->name('plan.create');
        Route::get('/plan/{plan}/edit', [PedController::class, 'editPlan'])->name('plan.edit');

        Route::post('/plan', [PedController::class, 'storePlan'])->name('plan.store');
        Route::put('/plan/{plan}', [PedController::class, 'updatePlan'])->name('plan.update');
        Route::delete('/plan/{plan}', [PedController::class, 'destroyPlan'])->name('plan.destroy');

        Route::post('/eje', [PedController::class, 'storeEje'])->name('eje.store');
        Route::put('/eje/{eje}', [PedController::class, 'updateEje'])->name('eje.update');
        Route::delete('/eje/{eje}', [PedController::class, 'destroyEje'])->name('eje.destroy');

        Route::post('/tema', [PedController::class, 'storeTema'])->name('tema.store');
        Route::put('/tema/{tema}', [PedController::class, 'updateTema'])->name('tema.update');
        Route::delete('/tema/{tema}', [PedController::class, 'destroyTema'])->name('tema.destroy');

        Route::post('/objetivo', [PedController::class, 'storeObjetivo'])->name('objetivo.store');
        Route::put('/objetivo/{objetivo}', [PedController::class, 'updateObjetivo'])->name('objetivo.update');
        Route::delete('/objetivo/{objetivo}', [PedController::class, 'destroyObjetivo'])->name('objetivo.destroy');

        Route::post('/estrategia', [PedController::class, 'storeEstrategia'])->name('estrategia.store');
        Route::put('/estrategia/{estrategia}', [PedController::class, 'updateEstrategia'])->name('estrategia.update');
        Route::delete('/estrategia/{estrategia}', [PedController::class, 'destroyEstrategia'])->name('estrategia.destroy');

        Route::post('/linea', [PedController::class, 'storeLinea'])->name('linea.store');
        Route::put('/linea/{linea}', [PedController::class, 'updateLinea'])->name('linea.update');
        Route::delete('/linea/{linea}', [PedController::class, 'destroyLinea'])->name('linea.destroy');

        // Páginas de formulario (GET) — Nodo (eje, tema, objetivo, estrategia, linea)
        Route::get('/nodo/create', [PedController::class, 'createNodo'])->name('nodo.create');
        Route::get('/nodo/{tipo}/{id}/edit', [PedController::class, 'editNodo'])->name('nodo.edit');
    });
});
