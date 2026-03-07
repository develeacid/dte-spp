<?php

namespace Tests\Feature;

use App\Models\PedPlan;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedPlanActivoConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_pueden_existir_dos_planes_activos(): void
    {
        // Crear primer plan activo
        $plan1 = PedPlan::create([
            'nombre' => 'Plan 1',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        // Intentar crear segundo plan activo debe fallar
        $this->expectException(QueryException::class);

        PedPlan::create([
            'nombre' => 'Plan 2',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);
    }

    public function test_si_pueden_existir_multiples_planes_inactivos(): void
    {
        PedPlan::create([
            'nombre' => 'Plan Inactivo 1',
            'periodo_inicio' => 2019,
            'periodo_fin' => 2024,
            'activo' => false,
        ]);

        PedPlan::create([
            'nombre' => 'Plan Inactivo 2',
            'periodo_inicio' => 2013,
            'periodo_fin' => 2018,
            'activo' => false,
        ]);

        $this->assertEquals(2, PedPlan::where('activo', false)->count());
    }

    public function test_metodo_activar_desactiva_los_demas(): void
    {
        $plan1 = PedPlan::create([
            'nombre' => 'Plan 1',
            'periodo_inicio' => 2019,
            'periodo_fin' => 2024,
            'activo' => true,
        ]);

        $plan2 = PedPlan::create([
            'nombre' => 'Plan 2',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => false,
        ]);

        // Activar plan2
        $plan2->activar();

        // Verificar
        $this->assertFalse($plan1->fresh()->activo);
        $this->assertTrue($plan2->fresh()->activo);
        $this->assertEquals($plan2->id, PedPlan::planActivo()->id);
    }
}
