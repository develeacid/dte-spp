<?php

namespace App\Observers;

use App\Models\Juridico\SustentoLegalPrograma;
use App\Services\EstadoConsolidadoService;
use App\Services\Juridico\ValidacionJuridicaService;

class SustentoLegalObserver
{
    public function saved(SustentoLegalPrograma $sustento): void
    {
        $this->recalcular($sustento);
    }

    public function deleted(SustentoLegalPrograma $sustento): void
    {
        $this->recalcular($sustento);
    }

    private function recalcular(SustentoLegalPrograma $sustento): void
    {
        $ejercicio = config('presupuesto.ejercicio_default');

        app(ValidacionJuridicaService::class)->recalcularChecklist(
            $sustento->programa_presupuestario_id,
            $ejercicio
        );

        app(EstadoConsolidadoService::class)->recalcular(
            $sustento->programa_presupuestario_id,
            $ejercicio
        );
    }
}
