<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceEvidencia;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AvanceActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $operador;

    private MetaPeriodo $metaPeriodo;

    private Indicador $indicador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->operador = User::factory()->withPersonalTeam()->create();
        $this->operador->assignRole('operador');

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test',
            'clave' => 'PC-001',
            'team_id' => $this->operador->currentTeam->id,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test',
            'orden' => 1,
            'team_id' => $this->operador->currentTeam->id,
        ]);

        $this->indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador de prueba',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $this->metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);
    }

    public function test_creating_avance_produces_activity_log_entry(): void
    {
        $avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->operador->id,
        ]);

        $activity = Activity::where('subject_type', Avance::class)
            ->where('subject_id', $avance->id)
            ->latest()
            ->first();

        $this->assertNotNull($activity, 'Activity log entry was not created for Avance::create');
        $this->assertSame('created', $activity->event);
    }

    public function test_updating_avance_result_produces_activity_log_with_old_and_new_values(): void
    {
        $avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->operador->id,
            'resultado' => 10,
        ]);

        $avance->update(['resultado' => 20]);

        $activity = Activity::where('subject_type', Avance::class)
            ->where('subject_id', $avance->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($activity, 'Activity log entry was not created for Avance::update');

        $properties = $activity->properties->toArray();
        $this->assertArrayHasKey('old', $properties);
        $this->assertArrayHasKey('attributes', $properties);
        $this->assertSame('10.0000', $properties['old']['resultado']);
        $this->assertSame('20.0000', $properties['attributes']['resultado']);
    }

    public function test_avance_estado_change_is_audited(): void
    {
        $avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->operador->id,
        ]);

        $avance->update(['estado' => EstadoAvance::EN_REVISION->value]);

        $activity = Activity::where('subject_type', Avance::class)
            ->where('subject_id', $avance->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame(
            EstadoAvance::EN_CAPTURA->value,
            $activity->properties['old']['estado'] ?? null,
        );
        $this->assertSame(
            EstadoAvance::EN_REVISION->value,
            $activity->properties['attributes']['estado'] ?? null,
        );
    }

    public function test_deleting_avance_is_audited(): void
    {
        $avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->operador->id,
        ]);
        $avanceId = $avance->id;

        $avance->delete();

        $activity = Activity::where('subject_type', Avance::class)
            ->where('subject_id', $avanceId)
            ->where('event', 'deleted')
            ->latest()
            ->first();

        $this->assertNotNull($activity, 'Deletion was not audited');
    }

    public function test_creating_avance_evidencia_produces_activity_log_entry(): void
    {
        $avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->operador->id,
        ]);

        $evidencia = AvanceEvidencia::create([
            'avance_id' => $avance->id,
            'nombre_archivo' => 'test.pdf',
            'ruta_archivo' => 'evidencias/1/test.pdf',
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 1024,
            'hash_archivo' => str_repeat('a', 64),
            'nombre_documento' => 'Evidencia inicial',
            'subido_por' => $this->operador->id,
        ]);

        $activity = Activity::where('subject_type', AvanceEvidencia::class)
            ->where('subject_id', $evidencia->id)
            ->latest()
            ->first();

        $this->assertNotNull($activity, 'Activity log entry was not created for AvanceEvidencia::create');
        $this->assertSame('created', $activity->event);
    }

    public function test_updating_avance_evidencia_metadata_is_audited(): void
    {
        $avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->operador->id,
        ]);

        $evidencia = AvanceEvidencia::create([
            'avance_id' => $avance->id,
            'nombre_archivo' => 'orig.pdf',
            'ruta_archivo' => 'evidencias/1/orig.pdf',
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 1024,
            'hash_archivo' => str_repeat('b', 64),
            'subido_por' => $this->operador->id,
            'nombre_documento' => 'Documento original',
        ]);

        $evidencia->update(['nombre_documento' => 'Documento renombrado']);

        $activity = Activity::where('subject_type', AvanceEvidencia::class)
            ->where('subject_id', $evidencia->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('Documento original', $activity->properties['old']['nombre_documento'] ?? null);
        $this->assertSame('Documento renombrado', $activity->properties['attributes']['nombre_documento'] ?? null);
    }
}
