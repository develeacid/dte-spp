<?php

namespace Tests\Feature\Admin;

use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditMirTest extends TestCase
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
            'clave' => 'PP-TEST-'.uniqid(),
            'ejercicio_fiscal' => 2026,
            'origen' => 'nuevo',
            'estado' => 'borrador',
        ]);
    }

    public function test_mir_nivel_creation_is_logged(): void
    {
        $programa = $this->createPrograma();

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Contribuir al desarrollo',
            'orden' => 1,
        ]);

        $activity = Activity::where('subject_type', MirNivel::class)
            ->where('subject_id', $nivel->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('MirNivel created', $activity->description);
    }

    public function test_mir_nivel_update_logs_only_changed(): void
    {
        $programa = $this->createPrograma();

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Original',
            'orden' => 1,
        ]);

        Activity::query()->delete();

        $nivel->update(['resumen_narrativo' => 'Actualizado']);

        $activity = Activity::where('subject_type', MirNivel::class)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('resumen_narrativo', $activity->properties['attributes']);
        $this->assertArrayNotHasKey('orden', $activity->properties['attributes']);
    }

    public function test_indicador_creation_is_logged(): void
    {
        $programa = $this->createPrograma();
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Test',
            'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Tasa de cobertura',
            'formula_texto' => 'A/B*100',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => 'ascendente',
            'orden' => 1,
        ]);

        $activity = Activity::where('subject_type', Indicador::class)
            ->where('subject_id', $indicador->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('Indicador created', $activity->description);
    }
}
