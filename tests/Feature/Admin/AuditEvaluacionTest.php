<?php

namespace Tests\Feature\Admin;

use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditEvaluacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createPrograma(): ProgramaPresupuestario
    {
        return ProgramaPresupuestario::create([
            'nombre' => 'Programa de prueba',
            'clave' => 'PP-TEST-' . uniqid(),
            'ejercicio_fiscal' => 2026,
            'origen' => 'nuevo',
            'estado' => 'borrador',
        ]);
    }

    public function test_evaluacion_creation_is_logged(): void
    {
        $programa = $this->createPrograma();
        $user = User::factory()->withPersonalTeam()->create();

        $evaluacion = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'indice_eficacia' => 85.5000,
            'indicadores_evaluados' => 10,
            'indicadores_no_evaluados' => 2,
            'calculado_por' => $user->id,
        ]);

        $activity = Activity::where('subject_type', EvaluacionPrograma::class)
            ->where('subject_id', $evaluacion->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('EvaluacionPrograma created', $activity->description);
    }

    public function test_evaluacion_update_excludes_json_fields(): void
    {
        $programa = $this->createPrograma();
        $user = User::factory()->withPersonalTeam()->create();

        $evaluacion = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'indice_eficacia' => 80.0000,
            'indicadores_evaluados' => 8,
            'indicadores_no_evaluados' => 4,
            'calculado_por' => $user->id,
        ]);

        Activity::query()->delete();

        $evaluacion->update([
            'indice_eficacia' => 90.0000,
            'desglose_niveles' => ['fin' => 95],
        ]);

        $activity = Activity::where('subject_type', EvaluacionPrograma::class)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('indice_eficacia', $activity->properties['attributes']);
        $this->assertArrayNotHasKey('desglose_niveles', $activity->properties['attributes']);
    }
}
