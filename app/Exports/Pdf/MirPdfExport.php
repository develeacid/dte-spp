<?php

namespace App\Exports\Pdf;

use App\Models\ProgramaPresupuestario;
use Barryvdh\DomPDF\Facade\Pdf;

class MirPdfExport
{
    public function __construct(
        private ProgramaPresupuestario $programa,
        private int $ejercicioFiscal,
    ) {}

    public function generate(): string
    {
        $niveles = $this->programa->mirNiveles()
            ->with(['indicadores.mediosVerificacion', 'indicadores.variables'])
            ->orderByRaw("CASE tipo_nivel WHEN 'fin' THEN 1 WHEN 'proposito' THEN 2 WHEN 'componente' THEN 3 WHEN 'actividad' THEN 4 END")
            ->orderBy('orden')
            ->get();

        $encabezado = config('evaluation.exports.encabezado');

        $pdf = Pdf::loadView('exports.pdf.mir', [
            'programa' => $this->programa,
            'niveles' => $niveles,
            'ejercicioFiscal' => $this->ejercicioFiscal,
            'encabezado' => $encabezado,
            'generadoEn' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('letter', 'landscape');

        return $pdf->output();
    }
}
