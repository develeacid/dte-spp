<?php

use App\Http\Controllers\Portal\PortalDatasetController;
use App\Http\Controllers\Portal\PortalDownloadController;
use App\Http\Controllers\Portal\PortalIndexController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->prefix('transparencia')->name('portal.')->group(function () {
    Route::get('/', [PortalIndexController::class, 'index'])->name('index');
    Route::get('/datasets/{codigo}', [PortalDatasetController::class, 'show'])->name('dataset.show');
    Route::get('/datasets/{codigo}/descargar', [PortalDownloadController::class, 'csv'])->name('dataset.download');
});
