<?php

namespace App\Exports\Pdf;

use App\Models\ProgramaPresupuestario;
use Barryvdh\DomPDF\Facade\Pdf;

class AvanceTrimestralPdfExport
{
    public function __construct(
        private ProgramaPresupuestario $programa,
        private int $ejercicioFiscal,
        private int $trimestre,
    ) {}

    public function generate(): string
    {
        $niveles = $this->programa->mirNiveles()
            ->with([
                'indicadores' => fn ($q) => $q->where('activo_seguimiento', true),
                'indicadores.metasPeriodo' => fn ($q) => $q->where('ejercicio_fiscal', $this->ejercicioFiscal)
                    ->where('periodo', $this->trimestre),
                'indicadores.avances' => fn ($q) => $q->whereHas('metaPeriodo', fn ($mp) => $mp->where('ejercicio_fiscal', $this->ejercicioFiscal)
                    ->where('periodo', $this->trimestre)),
            ])
            ->orderByRaw("CASE tipo_nivel WHEN 'fin' THEN 1 WHEN 'proposito' THEN 2 WHEN 'componente' THEN 3 WHEN 'actividad' THEN 4 END")
            ->orderBy('orden')
            ->get();

        $encabezado = config('evaluation.exports.encabezado');
        $team = $this->programa->team;

        $pdf = Pdf::loadView('exports.pdf.avance-trimestral', [
            'programa' => $this->programa,
            'niveles' => $niveles,
            'ejercicioFiscal' => $this->ejercicioFiscal,
            'trimestre' => $this->trimestre,
            'encabezado' => $encabezado,
            'generadoEn' => now()->format('d/m/Y H:i'),
            'titular' => $team->titular,
            'dependencia' => $team->name,
            'fecha' => now()->format('d/m/Y'),
        ]);

        $pdf->setPaper('letter', 'landscape');

        return $pdf->output();
    }
}
