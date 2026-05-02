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
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ActivarPadronTest extends TestCase
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
    }

    private function programaSinPadronConComponente(): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
        ]);

        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'Componente C1 de prueba',
            'orden' => 1,
        ]);

        return $programa;
    }

    private function fakeProvisioningOk(): void
    {
        Http::fake([
            '*/programs' => Http::response(['data' => ['spp_program_id' => 1]], 201),
            '*/components' => Http::response(['data' => ['spp_mir_nivel_id' => 1]], 201),
        ]);
    }

    public function test_service_registra_programa_y_componentes_y_marca_activo(): void
    {
        $this->fakeProvisioningOk();

        $programa = $this->programaSinPadronConComponente();
        $this->assertFalse($programa->padron_geobase_activo);

        $result = app(PadronProvisioningService::class)->register($programa);

        $this->assertSame(1, $result['componentes_registrados']);
        $this->assertTrue($programa->fresh()->padron_geobase_activo);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'padron-provisioning',
            'subject_type' => ProgramaPresupuestario::class,
            'subject_id' => $programa->id,
        ]);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/programs')
            && $req['spp_program_id'] === $programa->id);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/components')
            && $req['spp_program_id'] === $programa->id);
    }

    public function test_planeador_puede_activar_padron_desde_livewire(): void
    {
        $this->fakeProvisioningOk();

        $programa = $this->programaSinPadronConComponente();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->assertSee('Sin vinculación a GeoBase')
            ->assertSee('Activar padrón en GeoBase')
            ->call('activarPadron')
            ->assertHasNoErrors();

        $this->assertTrue($programa->fresh()->padron_geobase_activo);
    }

    public function test_juridico_no_ve_boton_y_no_puede_activar(): void
    {
        $programa = $this->programaSinPadronConComponente();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::ANALISTA_JURIDICO->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->assertSee('Solicita a un planeador u operador')
            ->assertDontSee('Activar padrón en GeoBase')
            ->call('activarPadron')
            ->assertForbidden();

        $this->assertFalse($programa->fresh()->padron_geobase_activo);
    }

    public function test_geobase_falla_no_marca_activo_y_muestra_error(): void
    {
        Http::fake(['*/programs' => Http::response(['error' => 'down'], 500)]);

        $programa = $this->programaSinPadronConComponente();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->call('activarPadron')
            ->assertSet('errorMessage', fn ($msg) => str_contains($msg, 'Error al activar padrón'));

        $this->assertFalse($programa->fresh()->padron_geobase_activo);
    }

    public function test_programa_sin_componentes_no_se_puede_activar(): void
    {
        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => false]);
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->call('activarPadron')
            ->assertSet('errorMessage', fn ($msg) => str_contains($msg, 'Componente'));

        $this->assertFalse($programa->fresh()->padron_geobase_activo);
    }
}
