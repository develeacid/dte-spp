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

class PadronProgramaCalidadTest extends TestCase
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
        Queue::fake();
    }

    private function montar(array $coverage)
    {
        Http::fake([
            '*/snapshots*' => Http::response(['data' => [], 'meta' => []], 200),
            '*/coverage*' => Http::response($coverage, 200),
        ]);
        Http::preventStrayRequests();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        $componente = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'Componente C1',
            'orden' => 1,
        ]);
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        return Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->call('seleccionarComponente', $componente->id)
            ->call('toggleFuente');
    }

    public function test_mapea_calidad_desde_coverage(): void
    {
        $this->montar([
            'total_beneficiaries' => 4,
            'quality' => [
                'total_beneficiaries' => 4, 'complete_records' => 3, 'complete_pct' => 75.0,
                'total_enrollments' => 5, 'verified_enrollments' => 4, 'verified_pct' => 80.0,
            ],
        ])
            ->assertSet('kpis.calidad.completos', 3)
            ->assertSet('kpis.calidad.completos_pct', 75.0)
            ->assertSet('kpis.calidad.verificados_pct', 80.0)
            ->assertSee('Calidad del padrón')
            ->assertSee('75');
    }

    public function test_coverage_sin_quality_degrada_a_null(): void
    {
        $this->montar(['total_beneficiaries' => 4])
            ->assertSet('kpis.calidad', null);
    }
}
