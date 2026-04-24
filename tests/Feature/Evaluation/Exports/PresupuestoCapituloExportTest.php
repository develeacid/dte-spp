<?php

namespace Tests\Feature\Evaluation\Exports;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\ProgramaPresupuestario;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PresupuestoCapituloExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (SystemRole::cases() as $rol) {
            Role::findOrCreate($rol->value, 'web');
        }
        foreach (SystemPermission::cases() as $p) {
            Permission::findOrCreate($p->value, 'web');
        }
        Role::findByName(SystemRole::ANALISTA_FINANCIERO->value)
            ->givePermissionTo([
                SystemPermission::EXPORTAR_CUENTA_PUBLICA->value,
                SystemPermission::VER_DATOS_FINANCIEROS->value,
            ]);
        Role::findByName(SystemRole::ANALISTA_JURIDICO->value)
            ->givePermissionTo([SystemPermission::VER_DATOS_FINANCIEROS->value]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $this->get(route('evaluation.exportar.presupuesto-capitulo', $programa))
            ->assertRedirect('/login');
    }

    public function test_forbids_user_without_exportar_cuenta_publica(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ANALISTA_JURIDICO->value);

        $programa = ProgramaPresupuestario::factory()->create();

        $this->actingAs($user)
            ->get(route('evaluation.exportar.presupuesto-capitulo', $programa))
            ->assertForbidden();
    }

    public function test_returns_xlsx_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ANALISTA_FINANCIERO->value);

        $programa = ProgramaPresupuestario::factory()->create(['clave' => 'EDU-TEST']);

        $response = $this->actingAs($user)->get(
            route('evaluation.exportar.presupuesto-capitulo', $programa) . '?ejercicio=2026&trimestre=1'
        );

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('presupuesto_ejercido_EDU-TEST_2026_T1', $response->headers->get('content-disposition'));
    }

    public function test_aggregates_partidas_into_two_capitulos(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ANALISTA_FINANCIERO->value);

        $programa = ProgramaPresupuestario::factory()->create();
        PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'clave_partida' => '1101',
            'monto_aprobado' => 100000,
        ]);
        PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'clave_partida' => '1301',
            'monto_aprobado' => 50000,
        ]);
        PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'clave_partida' => '2501',
            'monto_aprobado' => 30000,
        ]);

        $response = $this->actingAs($user)->get(
            route('evaluation.exportar.presupuesto-capitulo', $programa) . '?ejercicio=2026&trimestre=1'
        );

        $response->assertOk();

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx') . '.xlsx';
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            copy($response->getFile()->getRealPath(), $tmp);
        } else {
            ob_start();
            $response->sendContent();
            file_put_contents($tmp, ob_get_clean());
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx')->load($tmp);
        $sheets = $spreadsheet->getSheetNames();

        $this->assertContains('Capítulos', $sheets);
        $this->assertContains('Partidas', $sheets);

        @unlink($tmp);
    }
}
