<?php

use App\Livewire\Transparencia\Datasets\CrearEntrega;
use App\Livewire\Transparencia\Datasets\Edit;
use App\Livewire\Transparencia\Datasets\EditarPlantilla;
use App\Livewire\Transparencia\Datasets\Index;
use App\Livewire\Transparencia\Datasets\Show;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])
    ->prefix('transparencia/datos-abiertos')
    ->name('transparencia.datos-abiertos.')
    ->group(function () {
        Route::get('/', Index::class)->name('index')->middleware('permission:ver_datasets_abiertos');

        // Rutas más específicas ANTES del catch-all {dataset} (importante por matching).
        Route::get('/{dataset}/editar', Edit::class)->name('edit')->middleware('permission:gestionar_dataset_abierto');
        Route::get('/{dataset}/crear-entrega', CrearEntrega::class)->name('crear-entrega')->middleware('permission:gestionar_dataset_abierto');
        Route::get('/{dataset}/editar-plantilla', EditarPlantilla::class)->name('editar-plantilla')->middleware('permission:aprobar_datos_abiertos');

        Route::get('/{dataset}', Show::class)->name('show')->middleware('permission:ver_datasets_abiertos');
    });
