<?php

namespace Tests\Feature\Reportes;

use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\Reportes\VwPresupuestoAprobado;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VwPresupuestoAprobadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_agrega_por_programa_y_capitulo(): void
    {
        $team = Team::factory()->create();
        $programa = ProgramaPresupuestario::factory()->create(['team_id' => $team->id]);

        // Dos partidas del capítulo 1000 + una del 4000.
        PartidaPresupuestal::create([
            'programa_presupuestario_id' => $programa->id, 'clave_partida' => '1000',
            'descripcion' => 'Servicios personales', 'monto_aprobado' => 1000, 'monto_modificado' => 1200,
            'ejercicio_fiscal' => 2026, 'team_id' => $team->id,
        ]);
        PartidaPresupuestal::create([
            'programa_presupuestario_id' => $programa->id, 'clave_partida' => '1100',
            'descripcion' => 'Remuneraciones', 'monto_aprobado' => 500, 'monto_modificado' => null,
            'ejercicio_fiscal' => 2026, 'team_id' => $team->id,
        ]);
        PartidaPresupuestal::create([
            'programa_presupuestario_id' => $programa->id, 'clave_partida' => '4000',
            'descripcion' => 'Subsidios', 'monto_aprobado' => 5000, 'monto_modificado' => 5000,
            'ejercicio_fiscal' => 2026, 'team_id' => $team->id,
        ]);

        $cap1000 = VwPresupuestoAprobado::where('programa_id', $programa->id)->where('capitulo', '1000')->first();
        $cap4000 = VwPresupuestoAprobado::where('programa_id', $programa->id)->where('capitulo', '4000')->first();

        $this->assertNotNull($cap1000);
        $this->assertEqualsWithDelta(1500, (float) $cap1000->monto_aprobado, 0.01);   // 1000 + 500
        $this->assertEqualsWithDelta(1200, (float) $cap1000->monto_modificado, 0.01);  // 1200 + 0 (null COALESCE)
        $this->assertSame('Servicios Personales', $cap1000->capitulo_label);

        $this->assertEqualsWithDelta(5000, (float) $cap4000->monto_aprobado, 0.01);
        $this->assertSame('Transferencias, Asignaciones, Subsidios', $cap4000->capitulo_label);
    }
}
