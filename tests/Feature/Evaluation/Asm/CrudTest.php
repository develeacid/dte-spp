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
}
