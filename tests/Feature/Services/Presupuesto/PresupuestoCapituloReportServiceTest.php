<?php

namespace Tests\Feature\Services\Presupuesto;

use App\Models\ProgramaPresupuestario;
use App\Models\Presupuesto\AvanceFinanciero;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Services\Presupuesto\PresupuestoCapituloReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresupuestoCapituloReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_capitulos_aggregates_partidas_by_chapter(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        // 2 partidas in capítulo 1000
        $p1 = PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'clave_partida' => '1101',
            'monto_aprobado' => 100000,
        ]);
        $p2 = PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'clave_partida' => '1301',
            'monto_aprobado' => 50000,
        ]);
        // 1 partida in capítulo 2000
        PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'clave_partida' => '2501',
            'monto_aprobado' => 20000,
        ]);

        AvanceFinanciero::factory()->create([
            'partida_presupuestal_id' => $p1->id,
            'trimestre' => 1,
            'monto_pagado' => 30000,
        ]);
        AvanceFinanciero::factory()->create([
            'partida_presupuestal_id' => $p2->id,
            'trimestre' => 1,
            'monto_pagado' => 10000,
        ]);

        $capitulos = (new PresupuestoCapituloReportService($programa, 2026, 1))->capitulos();

        $this->assertCount(2, $capitulos);

        $c1000 = $capitulos->firstWhere('capitulo', '1000');
        $this->assertSame('Servicios Personales', $c1000['label']);
        $this->assertSame(150000.0, (float) $c1000['aprobado']);
        $this->assertSame(40000.0, (float) $c1000['pagado']);
    }

    public function test_partidas_returns_detail_with_capitulo_column(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();
        PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'clave_partida' => '3301',
        ]);

        $partidas = (new PresupuestoCapituloReportService($programa, 2026, 1))->partidas();

        $this->assertCount(1, $partidas);
        $this->assertSame('3000', $partidas->first()['capitulo']);
        $this->assertSame('Servicios Generales', $partidas->first()['capitulo_label']);
    }
}
