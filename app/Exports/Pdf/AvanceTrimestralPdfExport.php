<?php

namespace App\Exports\Pdf;

use App\Exports\Excel\Sheets\EvidenciaPadronSheet;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Presupuesto\IaffFinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;

class AvanceTrimestralPdfExport
{
    public function __construct(
        private ProgramaPresupuestario $programa,
        private int $ejercicioFiscal,
        private int $trimestre,
        private ?User $user = null,
    ) {}

    public function generateHtml(): string
    {
        return view('exports.pdf.avance-trimestral', $this->viewData())->render();
    }

    public function generate(): string
    {
        $pdf = Pdf::loadView('exports.pdf.avance-trimestral', $this->viewData());
        $pdf->setPaper('letter', 'landscape');

        return $pdf->output();
    }

    private function viewData(): array
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

        $data = [
            'programa' => $this->programa,
            'niveles' => $niveles,
            'ejercicioFiscal' => $this->ejercicioFiscal,
            'trimestre' => $this->trimestre,
            'encabezado' => $encabezado,
            'generadoEn' => now()->format('d/m/Y H:i'),
            'titular' => $team?->titular,
            'dependencia' => $team?->name,
            'fecha' => now()->format('d/m/Y'),
            'partidas' => null,
            'totalesFinancieros' => null,
            'evidenciasPadron' => $this->programa->padron_geobase_activo
                ? (new EvidenciaPadronSheet(
                    $this->programa,
                    $this->ejercicioFiscal,
                    $this->trimestre,
                ))->collection()->all()
                : [],
        ];

        if ($this->user?->can('ver_datos_financieros')) {
            $service = new IaffFinancialReportService(
                $this->programa,
                $this->ejercicioFiscal,
                $this->trimestre,
            );
            $partidas = $service->rows();
            $data['partidas'] = $partidas;
            $data['totalesFinancieros'] = $service->totals($partidas);
        }

        return $data;
    }
}
