<?php

use App\Livewire\Admin\Auditoria;
use App\Livewire\Admin\GestionUsuarios;
use App\Livewire\Admin\MonitoreoIa;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'can:administrar_usuarios'])->prefix('admin')->name('admin.')->group(function () {
    // AI monitoring
    Route::get('/monitoreo-ia', MonitoreoIa::class)->name('monitoreo-ia');

    // Audit trail
    Route::get('/auditoria', Auditoria::class)->name('auditoria');
});

// User management — requires invitar_usuarios permission
Route::middleware(['auth:sanctum', 'verified', 'can:invitar_usuarios'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/usuarios', GestionUsuarios::class)->name('users');
});
