<?php

namespace App\Observers;

use App\Models\Juridico\DocumentoNormativo;
use App\Services\EstadoConsolidadoService;
use App\Services\Juridico\ValidacionJuridicaService;

class DocumentoNormativoObserver
{
    public function saved(DocumentoNormativo $documento): void
    {
        $this->recalcular($documento);
    }

    public function deleted(DocumentoNormativo $documento): void
    {
        $this->recalcular($documento);
    }

    private function recalcular(DocumentoNormativo $documento): void
    {
        $ejercicio = config('presupuesto.ejercicio_default');

        app(ValidacionJuridicaService::class)->recalcularChecklist(
            $documento->programa_presupuestario_id,
            $ejercicio
        );

        app(EstadoConsolidadoService::class)->recalcular(
            $documento->programa_presupuestario_id,
            $ejercicio
        );
    }
}
