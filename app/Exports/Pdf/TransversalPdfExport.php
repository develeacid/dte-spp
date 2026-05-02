<?php

namespace App\Exports\Pdf;

use App\Models\Evaluation\AnexoTransversal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class TransversalPdfExport
{
    public function __construct(
        private string $tipo,
        private int $ejercicioFiscal,
    ) {}

    public function generate(): string
    {
        $datos = match ($this->tipo) {
            'anexo' => $this->datosAnexo(),
            default => collect(),
        };

        $encabezado = config('evaluation.exports.encabezado');

        $pdf = Pdf::loadView('exports.pdf.transversal', [
            'tipo' => $this->tipo,
            'datos' => $datos,
            'ejercicioFiscal' => $this->ejercicioFiscal,
            'encabezado' => $encabezado,
            'generadoEn' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('letter', 'landscape');

        return $pdf->output();
    }

    private function datosAnexo(): Collection
    {
        return AnexoTransversal::activos()
            ->with([
                'indicadores' => fn ($q) => $q->where('activo_seguimiento', true),
                'indicadores.mirNivel.programa',
                'indicadores.avances.metaPeriodo',
            ])
            ->get();
    }
}
