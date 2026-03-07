<?php

use App\Http\Controllers\Cascade\MatrizAlineacionController;
use App\Http\Controllers\Cascade\PedController;
use App\Http\Controllers\Cascade\ProgramaDerivadoController;
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

    // ============================================
    // MATRIZ DE ALINEACIÓN
    // ============================================
    Route::prefix('alineacion')->name('alineacion.')->group(function () {

        Route::get('/', [MatrizAlineacionController::class, 'index'])->name('index');

        Route::prefix('ped-pnd')->name('ped-pnd.')->group(function () {
            Route::post('/', [MatrizAlineacionController::class, 'storePedPnd'])->name('store');
            Route::delete('/{pedObjetivo}/{pndObjetivo}', [MatrizAlineacionController::class, 'destroyPedPnd'])->name('destroy');
        });

        Route::prefix('pnd-ods')->name('pnd-ods.')->group(function () {
            Route::post('/', [MatrizAlineacionController::class, 'storePndOds'])->name('store');
            Route::delete('/{pndObjetivo}/{odsMeta}', [MatrizAlineacionController::class, 'destroyPndOds'])->name('destroy');
        });

        Route::prefix('linea-programa')->name('linea-programa.')->group(function () {
            Route::post('/', [MatrizAlineacionController::class, 'storeLineaPrograma'])->name('store');
            Route::delete('/{linea}/{programaObjetivo}', [MatrizAlineacionController::class, 'destroyLineaPrograma'])->name('destroy');
        });

        Route::prefix('search')->name('search.')->group(function () {
            Route::get('/ped-objetivos', [MatrizAlineacionController::class, 'searchPedObjetivos'])->name('ped-objetivos');
            Route::get('/pnd-objetivos', [MatrizAlineacionController::class, 'searchPndObjetivos'])->name('pnd-objetivos');
            Route::get('/ods-metas', [MatrizAlineacionController::class, 'searchOdsMetas'])->name('ods-metas');
            Route::get('/lineas-accion', [MatrizAlineacionController::class, 'searchLineasAccion'])->name('lineas-accion');
            Route::get('/programas-objetivos', [MatrizAlineacionController::class, 'searchProgramasObjetivos'])->name('programas-objetivos');
        });

        Route::get('/cadena/{lineaAccion}', [MatrizAlineacionController::class, 'showCadena'])->name('cadena.show');
    });

    Route::prefix('programas-derivados')->name('programas-derivados.')->group(function () {
        Route::get('/', [ProgramaDerivadoController::class, 'index'])->name('index');

        // CRUD Programas
        Route::post('/', [ProgramaDerivadoController::class, 'store'])->name('store');
        Route::put('/{programa}', [ProgramaDerivadoController::class, 'update'])->name('update');
        Route::delete('/{programa}', [ProgramaDerivadoController::class, 'destroy'])->name('destroy');

        // CRUD Objetivos (nested)
        Route::post('/{programa}/objetivos', [ProgramaDerivadoController::class, 'storeObjetivo'])->name('objetivos.store');
        Route::put('/{programa}/objetivos/{objetivo}', [ProgramaDerivadoController::class, 'updateObjetivo'])->name('objetivos.update');
        Route::delete('/{programa}/objetivos/{objetivo}', [ProgramaDerivadoController::class, 'destroyObjetivo'])->name('objetivos.destroy');
    });
});
