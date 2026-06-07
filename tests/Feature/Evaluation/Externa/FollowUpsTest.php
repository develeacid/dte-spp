<?php

namespace Tests\Feature\Evaluation\Externa;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Livewire\Evaluation\AsmIndex;
use App\Livewire\Evaluation\EvaluacionProgramaView;
use App\Models\Evaluation\Asm;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Evaluation\Recomendacion;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FollowUpsTest extends TestCase
{
    use RefreshDatabase;

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
            SystemPermission::EXPORTAR_REPORTES,
        ] as $p) {
            Permission::findOrCreate($p->value, 'web');
        }
    }

    private function userConPermisos(array $permisos): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->givePermissionTo($permisos);

        return $user;
    }

    // ---------------------------------------------------------------------
    // 1. AsmIndex — filtro por recomendacion + badge "Origen externo"
    // ---------------------------------------------------------------------

    public function test_asm_index_filtra_por_recomendacion(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();
        $recomendacion = Recomendacion::factory()->create();

        $vinculado = Asm::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'recomendacion_id' => $recomendacion->id,
            'descripcion_aspecto' => 'ASM derivado de recomendacion externa específica.',
        ]);

        $legacy = Asm::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'recomendacion_id' => null,
            'descripcion_aspecto' => 'ASM legacy sin origen externo definido.',
        ]);

        $user = $this->userConPermisos([SystemPermission::VER_ASM->value]);
        $this->actingAs($user);

        Livewire::test(AsmIndex::class, ['recomendacion' => $recomendacion->id])
            ->assertSee($vinculado->descripcion_aspecto)
            ->assertDontSee($legacy->descripcion_aspecto);
    }

    public function test_asm_index_muestra_badge_origen_externo_solo_en_vinculado(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();
        $recomendacion = Recomendacion::factory()->create();

        Asm::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'recomendacion_id' => $recomendacion->id,
            'descripcion_aspecto' => 'ASM vinculado a evaluacion externa.',
        ]);

        $user = $this->userConPermisos([SystemPermission::VER_ASM->value]);
        $this->actingAs($user);

        // Con filtro: solo el vinculado → badge presente
        Livewire::test(AsmIndex::class, ['recomendacion' => $recomendacion->id])
            ->assertSee('Origen externo');
    }

    public function test_asm_index_badge_ausente_en_asm_legacy(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        Asm::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'recomendacion_id' => null,
            'descripcion_aspecto' => 'ASM legacy unico sin origen externo.',
        ]);

        $user = $this->userConPermisos([SystemPermission::VER_ASM->value]);
        $this->actingAs($user);

        Livewire::test(AsmIndex::class)
            ->assertSee('ASM legacy unico sin origen externo.')
            ->assertDontSee('Origen externo');
    }

    // ---------------------------------------------------------------------
    // 3. EvaluacionProgramaView — link a evaluaciones externas gated
    // ---------------------------------------------------------------------

    private function crearEvaluacionPrograma(): EvaluacionPrograma
    {
        $programa = ProgramaPresupuestario::factory()->create();

        return EvaluacionPrograma::create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'indice_eficacia' => 72.5,
            'indicadores_evaluados' => 1,
            'indicadores_no_evaluados' => 0,
            'conteo_semaforos' => ['verde' => 1, 'amarillo' => 0, 'rojo' => 0, 'sin_dato' => 0],
            'desglose_niveles' => [],
            'configuracion_calculo' => [],
        ]);
    }

    public function test_evaluacion_programa_muestra_link_externas_con_permiso(): void
    {
        $evaluacion = $this->crearEvaluacionPrograma();

        $user = $this->userConPermisos([
            SystemPermission::EXPORTAR_REPORTES->value,
            SystemPermission::VER_EVALUACION_EXTERNA->value,
        ]);
        $this->actingAs($user);

        Livewire::test(EvaluacionProgramaView::class, ['evaluacion' => $evaluacion->id])
            ->assertSee('Evaluaciones externas')
            ->assertSeeHtml(route('evaluation.externas.index', ['programa' => $evaluacion->programa_presupuestario_id]));
    }

    public function test_evaluacion_programa_oculta_link_externas_sin_permiso(): void
    {
        $evaluacion = $this->crearEvaluacionPrograma();

        // exportar_reportes para poder entrar a la vista, pero sin ver_evaluacion_externa
        $user = $this->userConPermisos([SystemPermission::EXPORTAR_REPORTES->value]);
        $this->actingAs($user);

        Livewire::test(EvaluacionProgramaView::class, ['evaluacion' => $evaluacion->id])
            ->assertDontSee('Evaluaciones externas');
    }

    // ---------------------------------------------------------------------
    // 4. Sidebar — grupo Reportes gated por permiso propio
    // ---------------------------------------------------------------------

    public function test_sidebar_usuario_solo_ver_asm_ve_asms_y_no_exports(): void
    {
        $user = $this->userConPermisos([SystemPermission::VER_ASM->value]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Reportes');
        $response->assertSee('ASMs');
        $response->assertDontSee('Transversal');
        $response->assertDontSee('Desviaciones');
        $response->assertDontSee('Acumulado Anual');
    }

    public function test_sidebar_usuario_sin_permisos_reportes_no_ve_grupo(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('Reportes');
        $response->assertDontSee('ASMs');
    }
}
