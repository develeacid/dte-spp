<?php

namespace Tests\Feature\Mml;

use App\Enums\SystemRole;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\CoberturaPrograma;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\PadronPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CoberturaProgramaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PadronPermissionsSeeder::class);

        // The MirNivelGeoBaseObserver dispatches a sync job whenever a
        // Componente is created on a programa with padron_geobase_activo=true;
        // these tests don't care about that side effect, so we capture them.
        Queue::fake();
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

    private function userPlaneador(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        return $user;
    }

    public function test_estado_inactivo_si_programa_sin_padron_activo(): void
    {
        Http::preventStrayRequests();

        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
        ]);

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'inactivo')
            ->assertSee('El padrón de este programa no está activo en GeoBase');
    }

    public function test_estado_ok_renderiza_kpis_y_tabla_municipios(): void
    {
        Http::fake([
            '*/programs/*/coverage' => Http::response([
                'spp_program_id' => 1,
                'program_name' => 'Programa Demo',
                'total_enrollments' => 250,
                'total_beneficiaries' => 200,
                'by_status' => ['aprobado' => 180, 'solicitado' => 70],
                'by_municipality' => [
                    ['municipality' => 'Oaxaca de Juárez', 'count' => 120],
                    ['municipality' => 'San Pablo', 'count' => 80],
                ],
            ], 200),
        ]);

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'ok')
            ->assertSeeHtml('text-emerald-700">200</p>')   // KPI total_beneficiaries
            ->assertSeeHtml('text-blue-700">250</p>')      // KPI total_enrollments
            ->assertSee('aprobado')                         // chip label, no collision
            ->assertSeeHtml('<strong class="ml-1">180</strong>')  // status count
            ->assertSee('Oaxaca de Juárez')                 // municipality name, no collision
            ->assertSeeHtml('<td class="px-4 py-2 text-sm text-gray-900 text-right">120</td>') // municipality count
            ->assertDontSee('Aún no hay beneficiarios');
    }

    public function test_estado_vacio_si_total_beneficiaries_es_cero(): void
    {
        Http::fake([
            '*/programs/*/coverage' => Http::response([
                'spp_program_id' => 1,
                'program_name' => 'Programa Demo',
                'total_enrollments' => 0,
                'total_beneficiaries' => 0,
                'by_status' => [],
                'by_municipality' => [],
            ], 200),
        ]);

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'vacio')
            ->assertSee('Aún no hay beneficiarios inscritos')
            ->assertDontSee('Total beneficiarios');
    }

    public function test_estado_no_registrado_si_geobase_devuelve_404(): void
    {
        Http::fake([
            '*/programs/*/coverage' => Http::response(['message' => 'Not Found'], 404),
        ]);

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'no_registrado')
            ->assertSee('El programa no está registrado en GeoBase')
            ->assertSee('geobase:register-program');
    }

    public function test_estado_error_si_geobase_devuelve_5xx_con_mensaje_sanitizado(): void
    {
        Http::fake([
            '*/programs/*/coverage' => Http::response('<html>fatal stack trace</html>', 500),
        ]);

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'error')
            ->assertSee('GeoBase no está disponible en este momento')
            ->assertDontSee('fatal stack trace')
            ->assertDontSee('<html>');
    }

    public function test_muestra_supuestos_del_proposito_y_componentes(): void
    {
        Http::fake([
            '*/programs/*/coverage' => Http::response([
                'total_enrollments' => 1, 'total_beneficiaries' => 1,
                'by_status' => [], 'by_municipality' => [],
            ], 200),
        ]);

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO,
            'resumen_narrativo' => 'Propósito del programa',
            'supuestos' => 'Las condiciones climáticas se mantienen estables',
            'orden' => 1,
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'Componente C1',
            'supuestos' => 'Los productores asisten a capacitaciones',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSee('Supuestos del MIR')
            ->assertSee('Propósito')
            ->assertSee('Las condiciones climáticas se mantienen estables')
            ->assertSee('Componente C1')
            ->assertSee('Los productores asisten a capacitaciones');
    }

    public function test_muestra_mensaje_si_sin_supuestos_definidos(): void
    {
        Http::fake([
            '*/programs/*/coverage' => Http::response([
                'total_enrollments' => 1, 'total_beneficiaries' => 1,
                'by_status' => [], 'by_municipality' => [],
            ], 200),
        ]);

        $programa = $this->programaConComponente(); // crea componente sin supuestos

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSee('Supuestos del MIR')
            ->assertSee('Sin Supuestos definidos');
    }

    public function test_muestra_timestamp_de_ultima_consulta_en_estado_ok(): void
    {
        Http::fake([
            '*/programs/*/coverage' => Http::response([
                'total_enrollments' => 1, 'total_beneficiaries' => 1,
                'by_status' => [], 'by_municipality' => [],
            ], 200),
        ]);

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSee('Consultado:')
            ->assertSet('consultadoAt', fn ($value) => is_string($value) && $value !== '');
    }
}
