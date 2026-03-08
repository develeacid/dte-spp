<?php

namespace App\Exports\Pdf;

use App\Models\Evaluation\EvaluacionPrograma;
use Barryvdh\DomPDF\Facade\Pdf;

class EvaluacionAnualPdfExport
{
    public function __construct(
        private EvaluacionPrograma $evaluacion,
    ) {}

    public function generate(): string
    {
        $this->evaluacion->load('programa');

        $programa = $this->evaluacion->programa;

        $niveles = $programa->mirNiveles()
            ->with([
                'indicadores' => fn ($q) => $q->where('activo_seguimiento', true),
                'indicadores.avances.metaPeriodo',
            ])
            ->orderByRaw("CASE tipo_nivel WHEN 'fin' THEN 1 WHEN 'proposito' THEN 2 WHEN 'componente' THEN 3 WHEN 'actividad' THEN 4 END")
            ->orderBy('orden')
            ->get();

        $encabezado = config('evaluation.exports.encabezado');

        $pdf = Pdf::loadView('exports.pdf.evaluacion-anual', [
            'evaluacion' => $this->evaluacion,
            'programa' => $programa,
            'niveles' => $niveles,
            'encabezado' => $encabezado,
            'generadoEn' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->output();
    }
}
