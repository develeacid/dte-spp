<?php

use App\Http\Controllers\Portal\PortalIndexController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->prefix('transparencia')->name('portal.')->group(function () {
    Route::get('/', [PortalIndexController::class, 'index'])->name('index');
});
