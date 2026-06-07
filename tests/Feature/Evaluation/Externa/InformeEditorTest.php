<?php

namespace Tests\Feature\Evaluation\Externa;

use App\Enums\PrioridadRecomendacion;
use App\Enums\SeveridadHallazgo;
use App\Enums\SystemRole;
use App\Livewire\Evaluation\InformeEvaluacionEditor;
use App\Models\Evaluation\Asm;
use App\Models\Evaluation\EvaluacionExterna;
use App\Models\Evaluation\Hallazgo;
use App\Models\Evaluation\InformeEvaluacion;
use App\Models\Evaluation\Recomendacion;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\Evaluation\EvaluacionExternaPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InformeEditorTest extends TestCase
{
    use RefreshDatabase;

    private User $planeador;

    private User $operador;

    private EvaluacionExterna $externa;

    private InformeEvaluacion $informe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EvaluacionExternaPermissionsSeeder::class);

        $this->planeador = User::factory()->create();
        $this->planeador->assignRole(SystemRole::PLANEADOR->value);

        $this->operador = User::factory()->create();
        $this->operador->assignRole(SystemRole::OPERADOR->value);

        $programa = ProgramaPresupuestario::factory()->create(['clave' => 'E001']);
        $this->externa = EvaluacionExterna::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'evaluador_externo' => 'Despacho Consultor SC',
        ]);
        $this->informe = InformeEvaluacion::factory()->create([
            'evaluacion_externa_id' => $this->externa->id,
            'resumen_ejecutivo' => 'Resumen seedeado del informe',
        ]);

        $this->actingAs($this->planeador);
    }

    public function test_render_completo_muestra_secciones_y_datos(): void
    {
        $hallazgo = Hallazgo::factory()->create([
            'informe_evaluacion_id' => $this->informe->id,
            'descripcion' => 'Hallazgo seedeado relevante',
            'severidad' => SeveridadHallazgo::ALTA->value,
        ]);
        Recomendacion::factory()->create([
            'hallazgo_id' => $hallazgo->id,
            'descripcion' => 'Recomendación seedeada relevante',
        ]);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->assertOk()
            ->assertSee('Resumen ejecutivo')
            ->assertSee('Metodología')
            ->assertSee('Hallazgos')
            ->assertSee('Conclusiones')
            ->assertSee('Recomendaciones')
            ->assertSee('Fichas')
            ->assertSee('Resumen seedeado del informe')
            ->assertSee('Hallazgo seedeado relevante')
            ->assertSee('Recomendación seedeada relevante')
            ->assertSee('Despacho Consultor SC');
    }

    public function test_route_show_render_200_con_permiso_ver(): void
    {
        $this->actingAs($this->operador)
            ->get(route('evaluation.externas.show', $this->externa))
            ->assertOk();
    }

    public function test_mount_crea_informe_si_no_existe(): void
    {
        $sinInforme = EvaluacionExterna::factory()->create();
        $this->assertSame(0, InformeEvaluacion::where('evaluacion_externa_id', $sinInforme->id)->count());

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $sinInforme])
            ->assertOk();

        $this->assertSame(1, InformeEvaluacion::where('evaluacion_externa_id', $sinInforme->id)->count());
    }

    public function test_guardar_seccion_persiste(): void
    {
        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->call('guardarSeccion', 'metodologia', 'Metodología cualitativa aplicada')
            ->assertHasNoErrors();

        $this->assertSame('Metodología cualitativa aplicada', $this->informe->fresh()->metodologia);
    }

    public function test_guardar_seccion_campo_fuera_de_whitelist_no_persiste(): void
    {
        $original = $this->informe->resumen_ejecutivo;

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->call('guardarSeccion', 'evaluacion_externa_id', '99999');

        // Nada cambió en el informe (campo no permitido)
        $this->assertSame($original, $this->informe->fresh()->resumen_ejecutivo);
        $this->assertSame($this->externa->id, $this->informe->fresh()->evaluacion_externa_id);
    }

    public function test_agregar_hallazgo_valido_persiste(): void
    {
        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->set('nuevoHallazgo.descripcion', 'Hallazgo con descripción suficientemente larga')
            ->set('nuevoHallazgo.severidad', SeveridadHallazgo::MEDIA->value)
            ->set('nuevoHallazgo.evidencia_url', 'https://ejemplo.test/evidencia.pdf')
            ->call('agregarHallazgo')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('hallazgos', [
            'informe_evaluacion_id' => $this->informe->id,
            'descripcion' => 'Hallazgo con descripción suficientemente larga',
            'severidad' => SeveridadHallazgo::MEDIA->value,
            'evidencia_url' => 'https://ejemplo.test/evidencia.pdf',
        ]);
    }

    public function test_agregar_hallazgo_descripcion_corta_error(): void
    {
        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->set('nuevoHallazgo.descripcion', 'corto')
            ->set('nuevoHallazgo.severidad', SeveridadHallazgo::MEDIA->value)
            ->call('agregarHallazgo')
            ->assertHasErrors(['nuevoHallazgo.descripcion']);

        $this->assertSame(0, Hallazgo::count());
    }

    public function test_agregar_hallazgo_url_invalida_error(): void
    {
        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->set('nuevoHallazgo.descripcion', 'Hallazgo con descripción suficientemente larga')
            ->set('nuevoHallazgo.severidad', SeveridadHallazgo::MEDIA->value)
            ->set('nuevoHallazgo.evidencia_url', 'no-es-una-url')
            ->call('agregarHallazgo')
            ->assertHasErrors(['nuevoHallazgo.evidencia_url']);

        $this->assertSame(0, Hallazgo::count());
    }

    public function test_agregar_recomendacion_anidada_persiste_bajo_hallazgo_correcto(): void
    {
        $hallazgo = Hallazgo::factory()->create(['informe_evaluacion_id' => $this->informe->id]);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->set("nuevaRecomendacion.{$hallazgo->id}.descripcion", 'Recomendación con texto suficiente')
            ->set("nuevaRecomendacion.{$hallazgo->id}.prioridad", PrioridadRecomendacion::ALTA->value)
            ->call('agregarRecomendacion', $hallazgo->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('recomendaciones', [
            'hallazgo_id' => $hallazgo->id,
            'descripcion' => 'Recomendación con texto suficiente',
            'prioridad' => PrioridadRecomendacion::ALTA->value,
        ]);
    }

    public function test_eliminar_hallazgo_cascadea_recomendaciones(): void
    {
        $hallazgo = Hallazgo::factory()->create(['informe_evaluacion_id' => $this->informe->id]);
        $reco = Recomendacion::factory()->create(['hallazgo_id' => $hallazgo->id]);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->call('eliminarHallazgo', $hallazgo->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('hallazgos', ['id' => $hallazgo->id]);
        $this->assertDatabaseMissing('recomendaciones', ['id' => $reco->id]);
    }

    public function test_eliminar_recomendacion_persiste(): void
    {
        $hallazgo = Hallazgo::factory()->create(['informe_evaluacion_id' => $this->informe->id]);
        $reco = Recomendacion::factory()->create(['hallazgo_id' => $hallazgo->id]);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->call('eliminarRecomendacion', $reco->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('recomendaciones', ['id' => $reco->id]);
    }

    public function test_eliminar_hallazgo_de_otra_evaluacion_falla(): void
    {
        $otraExterna = EvaluacionExterna::factory()->create();
        $otroInforme = InformeEvaluacion::factory()->create(['evaluacion_externa_id' => $otraExterna->id]);
        $hallazgoAjeno = Hallazgo::factory()->create(['informe_evaluacion_id' => $otroInforme->id]);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->call('eliminarHallazgo', $hallazgoAjeno->id);

        // El hallazgo ajeno sigue existiendo (scoping protege)
        $this->assertDatabaseHas('hallazgos', ['id' => $hallazgoAjeno->id]);
    }

    public function test_eliminar_recomendacion_de_otra_evaluacion_falla(): void
    {
        $otraExterna = EvaluacionExterna::factory()->create();
        $otroInforme = InformeEvaluacion::factory()->create(['evaluacion_externa_id' => $otraExterna->id]);
        $hallazgoAjeno = Hallazgo::factory()->create(['informe_evaluacion_id' => $otroInforme->id]);
        $recoAjena = Recomendacion::factory()->create(['hallazgo_id' => $hallazgoAjeno->id]);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->call('eliminarRecomendacion', $recoAjena->id);

        $this->assertDatabaseHas('recomendaciones', ['id' => $recoAjena->id]);
    }

    public function test_agregar_recomendacion_a_hallazgo_de_otra_evaluacion_falla(): void
    {
        $otraExterna = EvaluacionExterna::factory()->create();
        $otroInforme = InformeEvaluacion::factory()->create(['evaluacion_externa_id' => $otraExterna->id]);
        $hallazgoAjeno = Hallazgo::factory()->create(['informe_evaluacion_id' => $otroInforme->id]);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->set("nuevaRecomendacion.{$hallazgoAjeno->id}.descripcion", 'Recomendación con texto suficiente')
            ->set("nuevaRecomendacion.{$hallazgoAjeno->id}.prioridad", PrioridadRecomendacion::ALTA->value)
            ->call('agregarRecomendacion', $hallazgoAjeno->id);

        $this->assertSame(0, Recomendacion::where('hallazgo_id', $hallazgoAjeno->id)->count());
    }

    public function test_usuario_ver_only_no_puede_guardar_seccion(): void
    {
        $this->actingAs($this->operador);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->call('guardarSeccion', 'metodologia', 'intento no autorizado')
            ->assertForbidden();
    }

    public function test_usuario_ver_only_no_puede_agregar_hallazgo(): void
    {
        $this->actingAs($this->operador);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->set('nuevoHallazgo.descripcion', 'Hallazgo con descripción suficientemente larga')
            ->set('nuevoHallazgo.severidad', SeveridadHallazgo::MEDIA->value)
            ->call('agregarHallazgo')
            ->assertForbidden();

        $this->assertSame(0, Hallazgo::count());
    }

    public function test_usuario_ver_only_no_puede_eliminar_hallazgo(): void
    {
        $hallazgo = Hallazgo::factory()->create(['informe_evaluacion_id' => $this->informe->id]);

        $this->actingAs($this->operador);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->call('eliminarHallazgo', $hallazgo->id)
            ->assertForbidden();

        $this->assertDatabaseHas('hallazgos', ['id' => $hallazgo->id]);
    }

    public function test_usuario_ver_only_no_puede_agregar_recomendacion(): void
    {
        $hallazgo = Hallazgo::factory()->create(['informe_evaluacion_id' => $this->informe->id]);

        $this->actingAs($this->operador);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->set("nuevaRecomendacion.{$hallazgo->id}.descripcion", 'Recomendación con texto suficiente')
            ->set("nuevaRecomendacion.{$hallazgo->id}.prioridad", PrioridadRecomendacion::ALTA->value)
            ->call('agregarRecomendacion', $hallazgo->id)
            ->assertForbidden();

        $this->assertSame(0, Recomendacion::where('hallazgo_id', $hallazgo->id)->count());
    }

    public function test_usuario_ver_only_no_puede_eliminar_recomendacion(): void
    {
        $hallazgo = Hallazgo::factory()->create(['informe_evaluacion_id' => $this->informe->id]);
        $reco = Recomendacion::factory()->create(['hallazgo_id' => $hallazgo->id]);

        $this->actingAs($this->operador);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->call('eliminarRecomendacion', $reco->id)
            ->assertForbidden();

        $this->assertDatabaseHas('recomendaciones', ['id' => $reco->id]);
    }

    public function test_confirm_eliminar_hallazgo_avisa_asms_desvinculados(): void
    {
        $hallazgo = Hallazgo::factory()->create(['informe_evaluacion_id' => $this->informe->id]);
        $reco = Recomendacion::factory()->create(['hallazgo_id' => $hallazgo->id]);
        Asm::factory()->create(['recomendacion_id' => $reco->id]);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->assertSee('quedarán desvinculados');
    }

    public function test_contador_asms_derivados_visible(): void
    {
        $hallazgo = Hallazgo::factory()->create(['informe_evaluacion_id' => $this->informe->id]);
        $reco = Recomendacion::factory()->create(['hallazgo_id' => $hallazgo->id]);

        Asm::factory()->count(2)->create(['recomendacion_id' => $reco->id]);

        Livewire::test(InformeEvaluacionEditor::class, ['evaluacionExterna' => $this->externa])
            ->assertSee('2 ASM derivados');
    }
}
