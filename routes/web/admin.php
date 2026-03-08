<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'can:administrar_usuarios'])->prefix('admin')->name('admin.')->group(function () {
    // AI monitoring
    Route::get('/monitoreo-ia', \App\Livewire\Admin\MonitoreoIa::class)->name('monitoreo-ia');

    // Audit trail
    Route::get('/auditoria', \App\Livewire\Admin\Auditoria::class)->name('auditoria');
});

// User management — requires invitar_usuarios permission
Route::middleware(['auth:sanctum', 'verified', 'can:invitar_usuarios'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/usuarios', \App\Livewire\Admin\GestionUsuarios::class)->name('users');
});
