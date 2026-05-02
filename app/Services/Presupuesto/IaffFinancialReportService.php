<?php

namespace App\Services\Presupuesto;

use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use Illuminate\Support\Collection;

class IaffFinancialReportService
{
    public function __construct(
        private readonly ProgramaPresupuestario $programa,
        private readonly int $ejercicioFiscal,
        private readonly int $trimestre,
    ) {}

    public function rows(): Collection
    {
        return PartidaPresupuestal::query()
            ->with(['avancesFinancieros' => fn ($q) => $q->where('trimestre', '<=', $this->trimestre)])
            ->where('programa_presupuestario_id', $this->programa->id)
            ->where('ejercicio_fiscal', $this->ejercicioFiscal)
            ->orderBy('clave_partida')
            ->get()
            ->map(fn (PartidaPresupuestal $p) => [
                'partida' => $p,
                'clave_partida' => $p->clave_partida,
                'descripcion' => $p->descripcion,
                'monto_aprobado' => (float) $p->monto_aprobado,
                'monto_modificado' => $p->monto_modificado !== null ? (float) $p->monto_modificado : null,
                'monto_efectivo' => (float) $p->monto_efectivo,
                'monto_comprometido' => (float) $p->avancesFinancieros->sum('monto_comprometido'),
                'monto_devengado' => (float) $p->avancesFinancieros->sum('monto_devengado'),
                'monto_pagado' => (float) $p->avancesFinancieros->sum('monto_pagado'),
                'porcentaje_ejercido' => $p->monto_efectivo > 0
                    ? round(($p->avancesFinancieros->sum('monto_pagado') / (float) $p->monto_efectivo) * 100, 2)
                    : 0.0,
            ]);
    }

    public function totals(Collection $rows): array
    {
        $aprobado = $rows->sum('monto_aprobado');
        $modificado = $rows->sum(fn ($r) => $r['monto_modificado'] ?? $r['monto_aprobado']);
        $pagado = $rows->sum('monto_pagado');

        return [
            'aprobado' => $aprobado,
            'modificado' => $modificado,
            'comprometido' => $rows->sum('monto_comprometido'),
            'devengado' => $rows->sum('monto_devengado'),
            'pagado' => $pagado,
            'porcentaje_ejercido' => $modificado > 0 ? round(($pagado / $modificado) * 100, 2) : 0.0,
        ];
    }
}
