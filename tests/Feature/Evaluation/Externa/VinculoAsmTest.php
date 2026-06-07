<?php

namespace Tests\Feature\Evaluation\Externa;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Enums\TipoAccionAsm;
use App\Enums\TipoPlazoAsm;
use App\Livewire\Evaluation\AsmForm;
use App\Livewire\Evaluation\AsmShow;
use App\Models\Evaluation\Asm;
use App\Models\Evaluation\EvaluacionExterna;
use App\Models\Evaluation\Hallazgo;
use App\Models\Evaluation\InformeEvaluacion;
use App\Models\Evaluation\Recomendacion;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VinculoAsmTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    private User $responsable;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (SystemRole::cases() as $rol) {
            Role::findOrCreate($rol->value, 'web');
        }
        foreach ([
            SystemPermission::VER_ASM,
            SystemPermission::GESTIONAR_ASM,
            SystemPermission::VER_EVALUACION_EXTERNA,
        ] as $p) {
            Permission::findOrCreate($p->value, 'web');
        }
        Role::findByName(SystemRole::PLANEADOR->value)
            ->givePermissionTo([
                SystemPermission::VER_ASM->value,
                SystemPermission::GESTIONAR_ASM->value,
                SystemPermission::VER_EVALUACION_EXTERNA->value,
            ]);

        $this->user = User::factory()->create();
        $this->user->assignRole(SystemRole::PLANEADOR->value);
        $this->programa = ProgramaPresupuestario::factory()->create();
        $this->responsable = User::factory()->create();

        $this->actingAs($this->user);
    }

    private function recomendacionParaPrograma(ProgramaPresupuestario $programa): Recomendacion
    {
        $externa = EvaluacionExterna::factory()->create([
            'programa_presupuestario_id' => $programa->id,
        ]);
        $informe = InformeEvaluacion::factory()->create([
            'evaluacion_externa_id' => $externa->id,
        ]);
        $hallazgo = Hallazgo::factory()->create([
            'informe_evaluacion_id' => $informe->id,
        ]);

        return Recomendacion::factory()->create([
            'hallazgo_id' => $hallazgo->id,
        ]);
    }

    public function test_creates_asm_linked_to_recomendacion_of_same_programa(): void
    {
        $recomendacion = $this->recomendacionParaPrograma($this->programa);

        Livewire::test(AsmForm::class)
            ->set('form.programa_presupuestario_id', $this->programa->id)
            ->set('form.recomendacion_id', $recomendacion->id)
            ->set('form.descripcion_aspecto', 'El proceso de licitación debe adelantarse al cuarto trimestre.')
            ->set('form.accion_mejora', 'Iniciar licitación en octubre del ejercicio fiscal anterior.')
            ->set('form.tipo_plazo', TipoPlazoAsm::CORTO->value)
            ->set('form.tipo_accion', TipoAccionAsm::OPERATIVO->value)
            ->set('form.responsable_id', $this->responsable->id)
            ->set('form.area_responsable', 'Subdirección de Adquisiciones')
            ->set('form.fecha_compromiso', '2026-10-01')
            ->call('save')
            ->assertHasNoErrors();

        $asm = Asm::first();
        $this->assertNotNull($asm);
        $this->assertSame($recomendacion->id, $asm->recomendacion_id);
    }

    public function test_rejects_recomendacion_from_other_programa(): void
    {
        $otroPrograma = ProgramaPresupuestario::factory()->create();
        $recomendacionAjena = $this->recomendacionParaPrograma($otroPrograma);

        Livewire::test(AsmForm::class)
            ->set('form.programa_presupuestario_id', $this->programa->id)
            ->set('form.recomendacion_id', $recomendacionAjena->id)
            ->set('form.descripcion_aspecto', 'El proceso de licitación debe adelantarse al cuarto trimestre.')
            ->set('form.accion_mejora', 'Iniciar licitación en octubre del ejercicio fiscal anterior.')
            ->set('form.tipo_plazo', TipoPlazoAsm::CORTO->value)
            ->set('form.tipo_accion', TipoAccionAsm::OPERATIVO->value)
            ->set('form.responsable_id', $this->responsable->id)
            ->set('form.area_responsable', 'Subdirección de Adquisiciones')
            ->set('form.fecha_compromiso', '2026-10-01')
            ->call('save')
            ->assertHasErrors(['form.recomendacion_id']);

        $this->assertSame(0, Asm::count());
    }

    public function test_changing_programa_resets_recomendacion_id(): void
    {
        $recomendacion = $this->recomendacionParaPrograma($this->programa);
        $otroPrograma = ProgramaPresupuestario::factory()->create();

        Livewire::test(AsmForm::class)
            ->set('form.programa_presupuestario_id', $this->programa->id)
            ->set('form.recomendacion_id', $recomendacion->id)
            ->assertSet('form.recomendacion_id', $recomendacion->id)
            ->set('form.programa_presupuestario_id', $otroPrograma->id)
            ->assertSet('form.recomendacion_id', null);
    }

    public function test_select_only_lists_recomendaciones_of_selected_programa(): void
    {
        $propia = $this->recomendacionParaPrograma($this->programa);
        $otroPrograma = ProgramaPresupuestario::factory()->create();
        $this->recomendacionParaPrograma($otroPrograma);

        $component = Livewire::test(AsmForm::class)
            ->set('form.programa_presupuestario_id', $this->programa->id);

        $ids = $component->viewData('recomendaciones')->pluck('id')->all();

        $this->assertSame([$propia->id], $ids);
    }

    public function test_select_is_empty_when_no_programa_selected(): void
    {
        $this->recomendacionParaPrograma($this->programa);

        $component = Livewire::test(AsmForm::class);

        $this->assertCount(0, $component->viewData('recomendaciones'));
    }

    public function test_show_renders_origen_chain_and_link_when_linked(): void
    {
        $recomendacion = $this->recomendacionParaPrograma($this->programa);
        $externa = $recomendacion->hallazgo->informe->evaluacionExterna;

        $asm = Asm::factory()->create([
            'programa_presupuestario_id' => $this->programa->id,
            'recomendacion_id' => $recomendacion->id,
        ]);

        Livewire::test(AsmShow::class, ['asm' => $asm])
            ->assertSee('Origen')
            ->assertSee($recomendacion->descripcion)
            ->assertSee(route('evaluation.externas.show', $externa));
    }

    public function test_show_omits_origen_block_for_legacy_asm_without_link(): void
    {
        $asm = Asm::factory()->create([
            'programa_presupuestario_id' => $this->programa->id,
            'recomendacion_id' => null,
        ]);

        Livewire::test(AsmShow::class, ['asm' => $asm])
            ->assertDontSee('Ver evaluación externa');
    }
}
