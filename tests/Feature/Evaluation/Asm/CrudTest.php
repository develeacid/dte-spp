<?php

namespace Tests\Feature\Evaluation\Asm;

use App\Enums\StatusAsm;
use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Enums\TipoAccionAsm;
use App\Enums\TipoPlazoAsm;
use App\Livewire\Evaluation\AsmForm;
use App\Models\Evaluation\Asm;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrudTest extends TestCase
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
        foreach ([SystemPermission::VER_ASM, SystemPermission::GESTIONAR_ASM] as $p) {
            Permission::findOrCreate($p->value, 'web');
        }
        Role::findByName(SystemRole::PLANEADOR->value)
            ->givePermissionTo([SystemPermission::VER_ASM->value, SystemPermission::GESTIONAR_ASM->value]);

        $this->user = User::factory()->create();
        $this->user->assignRole(SystemRole::PLANEADOR->value);
        $this->programa = ProgramaPresupuestario::factory()->create();
        $this->responsable = User::factory()->create();

        $this->actingAs($this->user);
    }

    public function test_creates_an_asm_with_valid_payload(): void
    {
        Livewire::test(AsmForm::class)
            ->set('form.programa_presupuestario_id', $this->programa->id)
            ->set('form.descripcion_aspecto', 'El proceso de licitación debe adelantarse al cuarto trimestre.')
            ->set('form.accion_mejora', 'Iniciar licitación en octubre del ejercicio fiscal anterior.')
            ->set('form.tipo_plazo', TipoPlazoAsm::CORTO->value)
            ->set('form.tipo_accion', TipoAccionAsm::OPERATIVO->value)
            ->set('form.responsable_id', $this->responsable->id)
            ->set('form.area_responsable', 'Subdirección de Adquisiciones')
            ->set('form.fecha_compromiso', '2026-10-01')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('evaluation.asms.index'));

        $asm = Asm::first();
        $this->assertNotNull($asm);
        $this->assertSame(StatusAsm::PENDIENTE, $asm->status);
        $this->assertSame(0, $asm->porcentaje_avance);
    }

    public function test_validates_required_fields(): void
    {
        Livewire::test(AsmForm::class)
            ->call('save')
            ->assertHasErrors([
                'form.programa_presupuestario_id',
                'form.descripcion_aspecto',
                'form.accion_mejora',
                'form.tipo_plazo',
                'form.tipo_accion',
                'form.responsable_id',
                'form.area_responsable',
                'form.fecha_compromiso',
            ]);
    }

    public function test_rejects_invalid_tipo_plazo(): void
    {
        Livewire::test(AsmForm::class)
            ->set('form.tipo_plazo', 'eterno')
            ->call('save')
            ->assertHasErrors(['form.tipo_plazo']);
    }

    public function test_updates_an_existing_asm(): void
    {
        $asm = Asm::factory()->create(['porcentaje_avance' => 10]);

        Livewire::test(AsmForm::class, ['asm' => $asm])
            ->set('form.porcentaje_avance', 50)
            ->set('form.observacion_ultimo_avance', 'Se concluyó fase 1.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('evaluation.asms.index'));

        $fresh = $asm->fresh();
        $this->assertSame(50, $fresh->porcentaje_avance);
        $this->assertSame('Se concluyó fase 1.', $fresh->observacion_ultimo_avance);
    }

    public function test_requires_fecha_cumplimiento_when_status_is_cumplido(): void
    {
        $asm = Asm::factory()->create();

        Livewire::test(AsmForm::class, ['asm' => $asm])
            ->set('form.status', 'cumplido')
            ->set('form.fecha_cumplimiento', null)
            ->call('save')
            ->assertHasErrors(['form.fecha_cumplimiento']);
    }

    public function test_rejects_porcentaje_avance_out_of_range(): void
    {
        Livewire::test(AsmForm::class)
            ->set('form.porcentaje_avance', 150)
            ->call('save')
            ->assertHasErrors(['form.porcentaje_avance']);
    }

    public function test_soft_deletes_an_asm_via_controller(): void
    {
        $asm = Asm::factory()->create();

        $this->delete(route('evaluation.asms.destroy', $asm))
            ->assertRedirect(route('evaluation.asms.index'));

        $this->assertNull(Asm::find($asm->id));
        $this->assertNotNull(Asm::withTrashed()->find($asm->id)?->deleted_at);
    }
}
