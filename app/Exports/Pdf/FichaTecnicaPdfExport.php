<?php

namespace App\Exports\Pdf;

use App\Models\Mml\Indicador;
use Barryvdh\DomPDF\Facade\Pdf;

class FichaTecnicaPdfExport
{
    public function __construct(
        private Indicador $indicador,
    ) {}

    public function generate(): string
    {
        $this->indicador->load([
            'mirNivel.programa',
            'variables',
            'mediosVerificacion',
            'metasPeriodo',
            'cremaaValidacion',
            'unidadMedida',
        ]);

        $encabezado = config('evaluation.exports.encabezado');

        $pdf = Pdf::loadView('exports.pdf.ficha-tecnica', [
            'indicador' => $this->indicador,
            'nivel' => $this->indicador->mirNivel,
            'programa' => $this->indicador->mirNivel->programa,
            'encabezado' => $encabezado,
            'generadoEn' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->output();
    }
}
