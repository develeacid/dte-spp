<?php

namespace Tests\Feature\Padron;

use App\Enums\SystemRole;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\PadronPrograma;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\AsmPermissionsSeeder;
use Database\Seeders\JuridicoPermissionsSeeder;
use Database\Seeders\PadronPermissionsSeeder;
use Database\Seeders\PresupuestoPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PadronProgramaViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PresupuestoPermissionsSeeder::class);
        $this->seed(JuridicoPermissionsSeeder::class);
        $this->seed(AsmPermissionsSeeder::class);
        $this->seed(PadronPermissionsSeeder::class);

        // The MirNivelGeoBaseObserver dispatches a sync job whenever a
        // Componente is created on a programa with padron_geobase_activo=true;
        // these tests don't care about that side effect, so we capture them.
        Queue::fake();

        Http::fake([
            '*/snapshots*' => Http::response(['data' => [], 'meta' => []], 200),
        ]);
    }

    private function programaConComponente(bool $padronActivo = true): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => $padronActivo,
        ]);

        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'Componente C1',
            'orden' => 1,
        ]);

        return $programa;
    }

    public function test_render_inicial_muestra_estructura_base(): void
    {
        $programa = $this->programaConComponente();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->assertSee('Padrón')
            ->assertSee('Generar snapshot del trimestre')
            ->assertSet('modoFuente', 'snapshot');
    }

    public function test_programa_sin_geobase_program_id_muestra_estado_vacio(): void
    {
        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => false]);
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->assertSee('aún no está vinculado a GeoBase');
    }

    public function test_programa_sin_componentes_muestra_estado_vacio(): void
    {
        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->assertSee('aún no tiene Componentes');
    }

    public function test_modo_vivo_disponible_cuando_hay_componente_seleccionado(): void
    {
        $programa = $this->programaConComponente();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->assertSet('modoVivoDisponible', true);
    }

    public function test_toggle_fuente_alterna_modo_y_consulta_coverage_en_vivo(): void
    {
        Http::fake([
            '*/components/*/coverage' => Http::response([
                'spp_mir_nivel_id' => 1,
                'component_name' => 'C1',
                'total_enrollments' => 200,
                'total_beneficiaries' => 175,
                'by_status' => ['aprobado' => 150, 'pendiente' => 50],
                'by_municipality' => [],
            ], 200),
            '*/snapshots*' => Http::response(['data' => [], 'meta' => []], 200),
        ]);

        $programa = $this->programaConComponente();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->assertSet('modoFuente', 'snapshot')
            ->call('toggleFuente')
            ->assertSet('modoFuente', 'vivo')
            ->assertSet('kpis.total', 175)
            ->call('toggleFuente')
            ->assertSet('modoFuente', 'snapshot');
    }

    public function test_juridico_no_puede_generar_snapshot(): void
    {
        $programa = $this->programaConComponente();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::ANALISTA_JURIDICO->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->call('generarSnapshot')
            ->assertForbidden();
    }
}
