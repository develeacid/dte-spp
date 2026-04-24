<?php

namespace Tests\Feature\Evaluation\Asm;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\Evaluation\Asm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class XlsxExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (SystemRole::cases() as $rol) {
            Role::findOrCreate($rol->value, 'web');
        }
        foreach ([SystemPermission::VER_ASM, SystemPermission::GESTIONAR_ASM, SystemPermission::EXPORTAR_REPORTES] as $p) {
            Permission::findOrCreate($p->value, 'web');
        }
        Role::findByName(SystemRole::PLANEADOR->value)
            ->givePermissionTo([
                SystemPermission::VER_ASM->value,
                SystemPermission::GESTIONAR_ASM->value,
                SystemPermission::EXPORTAR_REPORTES->value,
            ]);
        Role::findByName(SystemRole::ANALISTA_JURIDICO->value)
            ->givePermissionTo([SystemPermission::VER_ASM->value]);
    }

    public function test_forbids_export_to_users_without_exportar_reportes(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ANALISTA_JURIDICO->value);

        $this->actingAs($user)
            ->get('/evaluacion/asms/exportar/xlsx')
            ->assertForbidden();
    }

    public function test_returns_an_xlsx_file_for_authorized_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        Asm::factory()->count(2)->create();
        Asm::factory()->cumplido()->create();
        Asm::factory()->vencido()->create();

        $response = $this->actingAs($user)->get('/evaluacion/asms/exportar/xlsx');

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            $response->headers->get('content-type') ?? '',
        );

        $disposition = $response->headers->get('content-disposition') ?? '';
        $this->assertStringContainsString('asm_seguimiento_', $disposition);
    }
}
