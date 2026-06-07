<?php

namespace Tests\Feature\Evaluation\Externa;

use App\Enums\EstadoEvaluacionExterna;
use App\Enums\SystemRole;
use App\Enums\TipoEvaluacionExterna;
use App\Livewire\Evaluation\EvaluacionExternaForm;
use App\Models\Evaluation\EvaluacionExterna;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Evaluation\InformeEvaluacion;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\Evaluation\EvaluacionExternaPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormTest extends TestCase
{
    use RefreshDatabase;

    private User $planeador;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EvaluacionExternaPermissionsSeeder::class);

        $this->planeador = User::factory()->create();
        $this->planeador->assignRole(SystemRole::PLANEADOR->value);

        $this->programa = ProgramaPresupuestario::factory()->create();

        $this->actingAs($this->planeador);
    }

    public function test_create_persists_evaluacion_and_informe_one_to_one(): void
    {
        Livewire::test(EvaluacionExternaForm::class)
            ->set('form.programa_presupuestario_id', $this->programa->id)
            ->set('form.ejercicio_fiscal', 2025)
            ->set('form.tipo', TipoEvaluacionExterna::DISENO->value)
            ->set('form.evaluador_externo', 'Despacho Consultor SC')
            ->set('form.fecha_inicio', '2025-01-01')
            ->set('form.fecha_fin', '2025-06-01')
            ->set('form.estado', EstadoEvaluacionExterna::EN_PROCESO->value)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('evaluation.externas.index'));

        $this->assertDatabaseHas('evaluaciones_externas', [
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2025,
            'tipo' => TipoEvaluacionExterna::DISENO->value,
            'evaluador_externo' => 'Despacho Consultor SC',
        ]);

        $externa = EvaluacionExterna::first();

        $this->assertDatabaseHas('informes_evaluacion', [
            'evaluacion_externa_id' => $externa->id,
        ]);

        $this->assertSame(1, InformeEvaluacion::count());
    }

    public function test_edit_updates_without_creating_second_informe(): void
    {
        $externa = EvaluacionExterna::factory()->create([
            'evaluador_externo' => 'Original SC',
        ]);
        InformeEvaluacion::factory()->create(['evaluacion_externa_id' => $externa->id]);

        Livewire::test(EvaluacionExternaForm::class, ['evaluacionExterna' => $externa])
            ->set('form.evaluador_externo', 'Actualizado SC')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('evaluation.externas.index'));

        $this->assertSame('Actualizado SC', $externa->fresh()->evaluador_externo);
        $this->assertSame(1, InformeEvaluacion::where('evaluacion_externa_id', $externa->id)->count());
    }

    public function test_validates_required_programa(): void
    {
        Livewire::test(EvaluacionExternaForm::class)
            ->set('form.programa_presupuestario_id', null)
            ->call('save')
            ->assertHasErrors(['form.programa_presupuestario_id']);
    }

    public function test_validates_required_evaluador(): void
    {
        Livewire::test(EvaluacionExternaForm::class)
            ->set('form.evaluador_externo', '')
            ->call('save')
            ->assertHasErrors(['form.evaluador_externo']);
    }

    public function test_validates_fecha_fin_after_or_equal_fecha_inicio(): void
    {
        Livewire::test(EvaluacionExternaForm::class)
            ->set('form.fecha_inicio', '2025-06-01')
            ->set('form.fecha_fin', '2025-01-01')
            ->call('save')
            ->assertHasErrors(['form.fecha_fin']);
    }

    public function test_validates_invalid_tipo(): void
    {
        Livewire::test(EvaluacionExternaForm::class)
            ->set('form.tipo', 'inexistente')
            ->call('save')
            ->assertHasErrors(['form.tipo']);
    }

    public function test_operador_cannot_access_create(): void
    {
        $operador = User::factory()->create();
        $operador->assignRole(SystemRole::OPERADOR->value);

        $this->actingAs($operador)->get('/evaluacion/externas/crear')->assertForbidden();
    }

    public function test_planeador_can_access_create(): void
    {
        $this->actingAs($this->planeador)->get('/evaluacion/externas/crear')->assertOk();
    }

    public function test_vinculo_select_only_shows_evaluacion_programa_for_selected_programa_and_ejercicio(): void
    {
        $otroPrograma = ProgramaPresupuestario::factory()->create();

        $match = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2025,
        ]);
        // Mismo programa, otro ejercicio
        EvaluacionPrograma::create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2024,
        ]);
        // Otro programa, mismo ejercicio
        EvaluacionPrograma::create([
            'programa_presupuestario_id' => $otroPrograma->id,
            'ejercicio_fiscal' => 2025,
        ]);

        $component = Livewire::test(EvaluacionExternaForm::class)
            ->set('form.programa_presupuestario_id', $this->programa->id)
            ->set('form.ejercicio_fiscal', 2025);

        $ids = $component->viewData('evaluacionesPrograma')->pluck('id')->all();

        $this->assertSame([$match->id], $ids);
    }

    public function test_changing_programa_resets_evaluacion_programa_id(): void
    {
        $otroPrograma = ProgramaPresupuestario::factory()->create();

        $calculoA = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2025,
        ]);

        Livewire::test(EvaluacionExternaForm::class)
            ->set('form.programa_presupuestario_id', $this->programa->id)
            ->set('form.ejercicio_fiscal', 2025)
            ->set('form.evaluacion_programa_id', $calculoA->id)
            ->assertSet('form.evaluacion_programa_id', $calculoA->id)
            ->set('form.programa_presupuestario_id', $otroPrograma->id)
            ->assertSet('form.evaluacion_programa_id', null);
    }

    public function test_changing_ejercicio_resets_evaluacion_programa_id(): void
    {
        $calculoA = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2025,
        ]);

        Livewire::test(EvaluacionExternaForm::class)
            ->set('form.programa_presupuestario_id', $this->programa->id)
            ->set('form.ejercicio_fiscal', 2025)
            ->set('form.evaluacion_programa_id', $calculoA->id)
            ->assertSet('form.evaluacion_programa_id', $calculoA->id)
            ->set('form.ejercicio_fiscal', 2024)
            ->assertSet('form.evaluacion_programa_id', null);
    }

    public function test_cannot_save_evaluacion_programa_id_from_other_programa(): void
    {
        $otroPrograma = ProgramaPresupuestario::factory()->create();

        $calculoOtroPrograma = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $otroPrograma->id,
            'ejercicio_fiscal' => 2025,
        ]);

        Livewire::test(EvaluacionExternaForm::class)
            ->set('form.programa_presupuestario_id', $this->programa->id)
            ->set('form.ejercicio_fiscal', 2025)
            ->set('form.tipo', TipoEvaluacionExterna::DISENO->value)
            ->set('form.evaluador_externo', 'Despacho Consultor SC')
            ->set('form.estado', EstadoEvaluacionExterna::EN_PROCESO->value)
            ->set('form.evaluacion_programa_id', $calculoOtroPrograma->id)
            ->call('save')
            ->assertHasErrors(['form.evaluacion_programa_id']);

        $this->assertSame(0, EvaluacionExterna::count());
        $this->assertSame(0, InformeEvaluacion::count());
    }

    public function test_operador_cannot_access_edit(): void
    {
        $externa = EvaluacionExterna::factory()->create();

        $operador = User::factory()->create();
        $operador->assignRole(SystemRole::OPERADOR->value);

        $this->actingAs($operador)
            ->get("/evaluacion/externas/{$externa->id}/editar")
            ->assertForbidden();
    }

    public function test_planeador_can_access_edit(): void
    {
        $externa = EvaluacionExterna::factory()->create();

        $this->actingAs($this->planeador)
            ->get("/evaluacion/externas/{$externa->id}/editar")
            ->assertOk();
    }
}
