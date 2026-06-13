<?php

use App\Enums\SystemPermission;
use App\Http\Controllers\Mml\MapaCoberturaProgramaController;
use App\Livewire\Mml\AlineacionEstrategica;
use App\Livewire\Mml\ArbolObjetivosBuilder;
use App\Livewire\Mml\ArbolProblemaBuilder;
use App\Livewire\Mml\CalendarizarMetas;
use App\Livewire\Mml\CierreFiscalPanel;
use App\Livewire\Mml\CoberturaPrograma;
use App\Livewire\Mml\CompletarHuecos;
use App\Livewire\Mml\DashboardImportaciones;
use App\Livewire\Mml\DefinicionProblema;
use App\Livewire\Mml\EmbudoPoblaciones;
use App\Livewire\Mml\HistorialIaff;
use App\Livewire\Mml\ImportarPrograma;
use App\Livewire\Mml\ListaProgramas;
use App\Livewire\Mml\MirEditor;
use App\Livewire\Mml\PadronPrograma;
use App\Livewire\Mml\SeleccionAlternativas;
use App\Livewire\Mml\VincularAlineacion;
use Illuminate\Support\Facades\Route;

// Padrón vive bajo /mml/programas/{programa}/padron pero está fuera del
// grupo permission:editar_mir porque roles de solo-lectura (analista
// jurídico/financiero) deben poder consultarlo sin poder editar la MIR.
Route::prefix('mml/programas')
    ->middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
    ])
    ->group(function () {
        Route::get('/{programa}/padron', PadronPrograma::class)
            ->middleware('can:ver_padron')
            ->name('mml.padron');

        Route::get('/{programa}/cobertura', CoberturaPrograma::class)
            ->middleware('can:ver_padron')
            ->name('mml.cobertura');

        Route::get('/{programa}/cobertura/mapa.png', MapaCoberturaProgramaController::class)
            ->middleware('can:ver_padron')
            ->name('mml.cobertura.mapa');

        Route::get('/{programa}/iaff', HistorialIaff::class)
            ->middleware('can:firmar_iaff')
            ->name('mml.iaff');

        Route::get('/{programa}/cierre-fiscal', CierreFiscalPanel::class)
            ->middleware('can:gestionar_cierre_fiscal')
            ->name('mml.cierre-fiscal');
    });

Route::prefix('mml')
    ->middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
        'permission:'.SystemPermission::EDITAR_MIR->value,
    ])
    ->group(function () {

        // Lista de programas
        Route::get('/programas', ListaProgramas::class)
            ->name('mml.programas');

        // Flujo de importación MIR
        Route::prefix('importar')->group(function () {
            Route::get('/', DashboardImportaciones::class)
                ->name('mml.importaciones');

            Route::get('/nuevo', ImportarPrograma::class)
                ->name('mml.importar.nuevo');

            Route::get('/{importacion}/completar', CompletarHuecos::class)
                ->name('mml.importar.completar');

            Route::get('/{importacion}/vincular', VincularAlineacion::class)
                ->name('mml.importar.vincular');

            Route::get('/{importacion}/calendarizar', CalendarizarMetas::class)
                ->name('mml.importar.calendarizar');
        });

        // Etapas del MML para un programa
        Route::prefix('{programa}')
            ->group(function () {
                Route::get('/etapa/1', DefinicionProblema::class)
                    ->name('mml.etapa1');
                Route::get('/etapa/2', ArbolProblemaBuilder::class)
                    ->name('mml.etapa2');
                Route::get('/etapa/3', ArbolObjetivosBuilder::class)
                    ->name('mml.etapa3');
                Route::get('/etapa/4', SeleccionAlternativas::class)
                    ->name('mml.etapa4');
                Route::get('/etapa/5', EmbudoPoblaciones::class)
                    ->name('mml.etapa5');
                Route::get('/etapa/6', AlineacionEstrategica::class)
                    ->name('mml.etapa6');
                Route::get('/etapa/7/mir', MirEditor::class)
                    ->name('mml.mir');
            });
    });
