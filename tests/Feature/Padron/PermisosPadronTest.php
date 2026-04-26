<?php

namespace Tests\Feature\Padron;

use App\Enums\SystemRole;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\AsmPermissionsSeeder;
use Database\Seeders\JuridicoPermissionsSeeder;
use Database\Seeders\PadronPermissionsSeeder;
use Database\Seeders\PresupuestoPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermisosPadronTest extends TestCase
{
    use RefreshDatabase;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PresupuestoPermissionsSeeder::class);
        $this->seed(JuridicoPermissionsSeeder::class);
        $this->seed(AsmPermissionsSeeder::class);
        $this->seed(PadronPermissionsSeeder::class);

        $this->programa = ProgramaPresupuestario::factory()->create([
            'geobase_program_id' => 100,
        ]);
    }

    /** @dataProvider rolesQueVenPadron */
    public function test_rol_puede_ver_tab_padron(SystemRole $rol): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole($rol->value);

        $this->actingAs($user)
            ->get("/mml/programas/{$this->programa->id}/padron")
            ->assertOk();
    }

    public static function rolesQueVenPadron(): array
    {
        return [
            'admin' => [SystemRole::ADMIN],
            'planeador' => [SystemRole::PLANEADOR],
            'operador' => [SystemRole::OPERADOR],
            'analista_financiero' => [SystemRole::ANALISTA_FINANCIERO],
            'analista_juridico' => [SystemRole::ANALISTA_JURIDICO],
        ];
    }

    public function test_usuario_sin_permisos_recibe_403(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get("/mml/programas/{$this->programa->id}/padron")
            ->assertForbidden();
    }
}
