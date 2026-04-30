<?php

namespace Tests\Feature\Padron;

use App\Enums\SystemRole;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\PadronPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PadronShcpExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PadronPermissionsSeeder::class);
        Queue::fake();
        Carbon::setTestNow('2026-04-15 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function programa(): ProgramaPresupuestario
    {
        return ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => true,
            'clave' => 'TEST-001',
        ]);
    }

    private function fakeShcpResponse(int $rows = 1): void
    {
        $data = [];
        for ($i = 0; $i < $rows; $i++) {
            $data[] = [
                'clave_programa' => 'TEST-001',
                'curp' => 'XAXX01010'.str_pad((string) $i, 1, '0').'HDFXXX01',
                'nombre_completo' => "Beneficiario {$i}",
                'sexo' => 'M',
                'fecha_nacimiento' => '1990-05-15',
                'municipio_clave' => 'Oaxaca de Juárez',
                'monto' => 1500.00,
                'tipo_apoyo' => 'monetario',
            ];
        }

        Http::fake([
            '*/padron/shcp*' => Http::response([
                'data' => $data,
                'meta' => ['total' => $rows, 'spp_program_id' => 1, 'periodo' => '2026-Q2'],
            ], 200),
        ]);
    }

    public function test_planeador_puede_descargar_xlsx(): void
    {
        $this->fakeShcpResponse(rows: 3);
        $programa = $this->programa();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        $response = $this->actingAs($user)
            ->get(route('evaluation.padron-shcp', $programa));

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString('padron-shcp-TEST-001-2026-Q2.xlsx', $response->headers->get('Content-Disposition'));
    }

    public function test_operador_no_puede_descargar(): void
    {
        $this->fakeShcpResponse();
        $programa = $this->programa();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::OPERADOR->value);

        $this->actingAs($user)
            ->get(route('evaluation.padron-shcp', $programa))
            ->assertForbidden();
    }

    public function test_404_si_padron_no_activo(): void
    {
        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => false]);
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        $this->actingAs($user)
            ->get(route('evaluation.padron-shcp', $programa))
            ->assertNotFound();
    }

    public function test_periodo_explicito_se_usa_en_filename(): void
    {
        $this->fakeShcpResponse();
        $programa = $this->programa();
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        $response = $this->actingAs($user)
            ->get(route('evaluation.padron-shcp', $programa).'?periodo=2026-Q1');

        $response->assertOk();
        $this->assertStringContainsString('padron-shcp-TEST-001-2026-Q1.xlsx', $response->headers->get('Content-Disposition'));
    }
}
