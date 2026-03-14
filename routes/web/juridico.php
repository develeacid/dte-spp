<?php

use App\Http\Controllers\Juridico\DocumentoNormativoController;
use App\Livewire\Juridico;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->prefix('juridico')->group(function () {

    // --- Panel del Analista Jurídico ---
    Route::get('/', Juridico\PanelJuridico::class)
        ->middleware('can:ver_sustento_legal')
        ->name('juridico.dashboard');

    // --- Vista principal de sustento legal por programa ---
    Route::get('/programa/{programa}', Juridico\SustentoLegalPrograma::class)
        ->middleware('can:ver_sustento_legal')
        ->name('juridico.programa');

    // --- CRUD Fundamentos ---
    Route::middleware('can:gestionar_sustento_legal')->group(function () {
        Route::get('/programa/{programa}/fundamento/create', Juridico\FundamentoForm::class)
            ->name('juridico.fundamento.create');
        Route::get('/programa/{programa}/fundamento/{fundamento}/edit', Juridico\FundamentoForm::class)
            ->name('juridico.fundamento.edit');
    });

    // --- Documentos normativos ---
    Route::get('/programa/{programa}/documentos', Juridico\DocumentosNormativos::class)
        ->middleware('can:gestionar_reglas_operacion')
        ->name('juridico.documentos');

    // --- Descarga controlada de documentos ---
    Route::get('/documento/{documento}/download', [DocumentoNormativoController::class, 'download'])
        ->middleware('can:ver_sustento_legal')
        ->name('juridico.documento.download');

    // --- Validación jurídica ---
    Route::get('/programa/{programa}/validacion', Juridico\ValidacionJuridica::class)
        ->middleware('can:validar_sustento_legal')
        ->name('juridico.validacion');
});
