<?php

namespace Tests\Feature\Services\Presupuesto;

use App\Models\ProgramaPresupuestario;
use App\Models\Presupuesto\AvanceFinanciero;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Services\Presupuesto\IaffFinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IaffFinancialReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_rows_for_partidas_of_programa_and_ejercicio(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $p1 = PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'clave_partida' => '1101',
            'monto_aprobado' => 100000,
            'monto_modificado' => 110000,
        ]);
        $p2 = PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'clave_partida' => '2501',
            'monto_aprobado' => 50000,
            'monto_modificado' => null,
        ]);

        AvanceFinanciero::factory()->create([
            'partida_presupuestal_id' => $p1->id,
            'trimestre' => 1,
            'monto_comprometido' => 30000,
            'monto_devengado' => 25000,
            'monto_pagado' => 20000,
        ]);

        $service = new IaffFinancialReportService($programa, 2026, 1);
        $rows = $service->rows();

        $this->assertCount(2, $rows);

        $first = $rows->firstWhere('clave_partida', '1101');
        $this->assertSame(110000.0, (float) $first['monto_efectivo']);
        $this->assertSame(20000.0, (float) $first['monto_pagado']);
        $this->assertEqualsWithDelta(18.18, (float) $first['porcentaje_ejercido'], 0.01);
    }

    public function test_totals_aggregates_all_rows(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();
        $partida = PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'monto_aprobado' => 100000,
            'monto_modificado' => null,
        ]);
        AvanceFinanciero::factory()->create([
            'partida_presupuestal_id' => $partida->id,
            'trimestre' => 1,
            'monto_comprometido' => 40000,
            'monto_devengado' => 30000,
            'monto_pagado' => 25000,
        ]);

        $service = new IaffFinancialReportService($programa, 2026, 1);
        $totals = $service->totals($service->rows());

        $this->assertSame(100000.0, (float) $totals['aprobado']);
        $this->assertSame(100000.0, (float) $totals['modificado']);
        $this->assertSame(40000.0, (float) $totals['comprometido']);
        $this->assertSame(25000.0, (float) $totals['pagado']);
        $this->assertEqualsWithDelta(25.0, (float) $totals['porcentaje_ejercido'], 0.01);
    }

    public function test_excludes_partidas_from_other_ejercicio(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();
        PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2025,
        ]);

        $rows = (new IaffFinancialReportService($programa, 2026, 1))->rows();
        $this->assertCount(0, $rows);
    }
}
