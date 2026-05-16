<?php

namespace Tests\Feature\Padron;

use App\Enums\SystemRole;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\PadronPrograma;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Padron\PadronProvisioningService;
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

class DesactivarPadronTest extends TestCase
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
        // Componente is touched on a programa with padron_geobase_activo=true;
        // these tests don't care about that side-effect, so we capture them.
        Queue::fake();
    }

    private function programaActivoConComponente(): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => true,
        ]);

        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'Componente C1',
            'orden' => 1,
        ]);

        return $programa;
    }

    private function geoBaseFakes(array $overrides = []): array
    {
        return array_merge([
            '*/snapshots*' => Http::response(['data' => []], 200),
            '*/programs*' => Http::response(['data' => ['spp_program_id' => 1]], 200),
            '*/components*' => Http::response(['data' => ['spp_mir_nivel_id' => 1]], 200),
        ], $overrides);
    }

    private function fakeDeactivationOk(): void
    {
        Http::fake($this->geoBaseFakes());
        Http::preventStrayRequests();
    }

    public function test_service_marca_componentes_y_programa_inactivos(): void
    {
        $this->fakeDeactivationOk();

        $programa = $this->programaActivoConComponente();
        $this->assertTrue($programa->padron_geobase_activo);

        $result = app(PadronProvisioningService::class)->deactivate($programa);

        $this->assertSame(1, $result['componentes_desactivados']);
        $this->assertFalse($programa->fresh()->padron_geobase_activo);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/components')
            && $req['activo'] === false);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/programs')
            && $req['activo'] === false);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'padron-deactivation',
            'subject_type' => ProgramaPresupuestario::class,
            'subject_id' => $programa->id,
        ]);
    }

    public function test_planeador_puede_desactivar_via_livewire(): void
    {
        $this->fakeDeactivationOk();

        $programa = $this->programaActivoConComponente();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->call('desactivarPadron')
            ->assertHasNoErrors();

        $this->assertFalse($programa->fresh()->padron_geobase_activo);
    }

    public function test_juridico_no_puede_desactivar(): void
    {
        Http::fake($this->geoBaseFakes());
        Http::preventStrayRequests();

        $programa = $this->programaActivoConComponente();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::ANALISTA_JURIDICO->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->call('desactivarPadron')
            ->assertForbidden();

        $this->assertTrue($programa->fresh()->padron_geobase_activo);
    }

    public function test_geobase_falla_no_marca_inactivo(): void
    {
        Http::fake($this->geoBaseFakes([
            '*/programs*' => Http::response(['error' => 'down'], 500),
        ]));
        Http::preventStrayRequests();

        $programa = $this->programaActivoConComponente();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->call('desactivarPadron')
            ->assertSet('errorMessage', fn ($msg) => str_contains($msg, 'Error al desactivar'));

        $this->assertTrue($programa->fresh()->padron_geobase_activo);
    }

    public function test_idempotente_si_ya_inactivo(): void
    {
        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => false]);
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        Http::fake();

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->call('desactivarPadron')
            ->assertHasNoErrors();

        $this->assertFalse($programa->fresh()->padron_geobase_activo);
        Http::assertNothingSent();
    }
}
