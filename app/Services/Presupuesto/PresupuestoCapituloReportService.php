<?php

namespace App\Services\Presupuesto;

use App\Models\ProgramaPresupuestario;
use Illuminate\Support\Collection;

class PresupuestoCapituloReportService
{
    private readonly IaffFinancialReportService $iaff;

    public function __construct(
        private readonly ProgramaPresupuestario $programa,
        private readonly int $ejercicioFiscal,
        private readonly int $trimestre,
    ) {
        $this->iaff = new IaffFinancialReportService($programa, $ejercicioFiscal, $trimestre);
    }

    public function partidas(): Collection
    {
        return $this->iaff->rows()->map(function (array $row) {
            $capitulo = CogCapituloCategorizer::capitulo($row['clave_partida']);

            return $row + [
                'capitulo' => $capitulo ?? '0000',
                'capitulo_label' => $capitulo !== null
                    ? CogCapituloCategorizer::label($capitulo)
                    : 'Sin clasificar',
            ];
        });
    }

    public function capitulos(): Collection
    {
        return $this->partidas()
            ->groupBy('capitulo')
            ->map(function (Collection $rows, string $capitulo) {
                $aprobado = $rows->sum('monto_aprobado');
                $modificado = $rows->sum(fn ($r) => $r['monto_modificado'] ?? $r['monto_aprobado']);
                $pagado = $rows->sum('monto_pagado');

                return [
                    'capitulo' => $capitulo,
                    'label' => CogCapituloCategorizer::label($capitulo),
                    'aprobado' => $aprobado,
                    'modificado' => $modificado,
                    'comprometido' => $rows->sum('monto_comprometido'),
                    'devengado' => $rows->sum('monto_devengado'),
                    'pagado' => $pagado,
                    'porcentaje_ejercido' => $modificado > 0 ? round(($pagado / $modificado) * 100, 2) : 0.0,
                ];
            })
            ->values();
    }

    public function programa(): ProgramaPresupuestario
    {
        return $this->programa;
    }

    public function ejercicio(): int
    {
        return $this->ejercicioFiscal;
    }

    public function trimestre(): int
    {
        return $this->trimestre;
    }
}
