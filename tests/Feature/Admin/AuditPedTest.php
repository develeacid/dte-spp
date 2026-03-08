<?php

namespace Tests\Feature\Admin;

use App\Models\PedEje;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditPedTest extends TestCase
{
    use RefreshDatabase;

    public function test_ped_plan_creation_is_logged(): void
    {
        $plan = PedPlan::create([
            'nombre' => 'PED Test 2026-2033',
            'nivel_gobierno' => 'estatal',
            'periodo_inicio' => 2026,
            'periodo_fin' => 2033,
            'activo' => true,
        ]);

        $activity = Activity::where('subject_type', PedPlan::class)
            ->where('subject_id', $plan->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('PedPlan created', $activity->description);
    }

    public function test_ped_plan_update_logs_only_dirty(): void
    {
        $plan = PedPlan::create([
            'nombre' => 'PED Original',
            'nivel_gobierno' => 'estatal',
            'periodo_inicio' => 2026,
            'periodo_fin' => 2033,
            'activo' => false,
        ]);

        Activity::query()->delete(); // Clear creation log

        $plan->update(['activo' => true]);

        $activity = Activity::where('subject_type', PedPlan::class)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('activo', $activity->properties['attributes']);
        $this->assertArrayNotHasKey('nombre', $activity->properties['attributes']);
    }

    public function test_ped_eje_creation_is_logged(): void
    {
        $plan = PedPlan::create([
            'nombre' => 'PED Test',
            'nivel_gobierno' => 'estatal',
            'periodo_inicio' => 2026,
            'periodo_fin' => 2033,
            'activo' => true,
        ]);

        $eje = PedEje::create([
            'ped_plan_id' => $plan->id,
            'numero' => 1,
            'nombre' => 'Eje de prueba',
            'descripcion' => 'Descripción del eje',
        ]);

        $activity = Activity::where('subject_type', PedEje::class)
            ->where('subject_id', $eje->id)
            ->first();

        $this->assertNotNull($activity);
    }

    public function test_ped_tema_creation_is_logged(): void
    {
        $plan = PedPlan::create([
            'nombre' => 'PED Test',
            'nivel_gobierno' => 'estatal',
            'periodo_inicio' => 2026,
            'periodo_fin' => 2033,
            'activo' => true,
        ]);

        $eje = PedEje::create([
            'ped_plan_id' => $plan->id,
            'numero' => 1,
            'nombre' => 'Eje',
        ]);

        $tema = PedTema::create([
            'ped_eje_id' => $eje->id,
            'numero' => 1,
            'nombre' => 'Tema de prueba',
        ]);

        $activity = Activity::where('subject_type', PedTema::class)
            ->where('subject_id', $tema->id)
            ->first();

        $this->assertNotNull($activity);
    }

    public function test_ped_objetivo_estrategico_creation_is_logged(): void
    {
        $plan = PedPlan::create([
            'nombre' => 'PED Test',
            'nivel_gobierno' => 'estatal',
            'periodo_inicio' => 2026,
            'periodo_fin' => 2033,
            'activo' => true,
        ]);

        $eje = PedEje::create(['ped_plan_id' => $plan->id, 'numero' => 1, 'nombre' => 'Eje']);
        $tema = PedTema::create(['ped_eje_id' => $eje->id, 'numero' => 1, 'nombre' => 'Tema']);

        $objetivo = PedObjetivoEstrategico::create([
            'ped_tema_id' => $tema->id,
            'clave' => 'OE1',
            'descripcion' => 'Objetivo estratégico de prueba',
        ]);

        $activity = Activity::where('subject_type', PedObjetivoEstrategico::class)
            ->where('subject_id', $objetivo->id)
            ->first();

        $this->assertNotNull($activity);
    }
}
