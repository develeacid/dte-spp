<?php

namespace Tests\Feature;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\PresupuestoPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Limpieza estricta de caché para evitar interferencia entre tests
        // (RefreshDatabase resetea la BD pero no la caché de Spatie)
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PresupuestoPermissionsSeeder::class);
    }

    public function test_planeador_tiene_permiso_crear_programa(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        $this->assertTrue($user->hasPermissionTo(SystemPermission::CREAR_PROGRAMA->value));
    }

    public function test_operador_no_tiene_permiso_crear_programa(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::OPERADOR->value);

        $this->assertFalse($user->hasPermissionTo(SystemPermission::CREAR_PROGRAMA->value));
    }

    public function test_admin_tiene_todos_los_permisos_excepto_segregados(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ADMIN->value);

        // Permisos segregados: NO se otorgan a admin (segregación de funciones).
        // Mantener sincronizado con $permisosSegregados en RolesAndPermissionsSeeder.
        $segregados = [
            SystemPermission::APROBAR_DATOS_ABIERTOS,
        ];

        foreach (SystemPermission::cases() as $permiso) {
            if (in_array($permiso, $segregados, true)) {
                $this->assertFalse(
                    $user->hasPermissionTo($permiso->value),
                    "Admin NO debe tener {$permiso->value} (permiso segregado)"
                );
            } else {
                $this->assertTrue(
                    $user->hasPermissionTo($permiso->value),
                    "Admin debe tener {$permiso->value}"
                );
            }
        }
    }

    public function test_analista_financiero_tiene_permisos_presupuesto(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ANALISTA_FINANCIERO->value);

        $this->assertTrue($user->hasPermissionTo(SystemPermission::GESTIONAR_PRESUPUESTO->value));
        $this->assertTrue($user->hasPermissionTo(SystemPermission::CAPTURAR_AVANCE_FINANCIERO->value));
        $this->assertTrue($user->hasPermissionTo(SystemPermission::VER_DATOS_FINANCIEROS->value));
        $this->assertTrue($user->hasPermissionTo(SystemPermission::EXPORTAR_CUENTA_PUBLICA->value));
        $this->assertTrue($user->hasPermissionTo(SystemPermission::EXPORTAR_REPORTES->value));
    }

    public function test_analista_financiero_no_tiene_permisos_planeacion(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ANALISTA_FINANCIERO->value);

        $this->assertFalse($user->hasPermissionTo(SystemPermission::EDITAR_MIR->value));
        $this->assertFalse($user->hasPermissionTo(SystemPermission::GESTIONAR_CATALOGOS->value));
        $this->assertFalse($user->hasPermissionTo(SystemPermission::CREAR_PROGRAMA->value));
    }

    public function test_planeador_tiene_ver_datos_financieros(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        $this->assertTrue($user->hasPermissionTo(SystemPermission::VER_DATOS_FINANCIEROS->value));
        $this->assertTrue($user->hasPermissionTo(SystemPermission::EXPORTAR_CUENTA_PUBLICA->value));
        $this->assertFalse($user->hasPermissionTo(SystemPermission::GESTIONAR_PRESUPUESTO->value));
        $this->assertFalse($user->hasPermissionTo(SystemPermission::CAPTURAR_AVANCE_FINANCIERO->value));
    }
}
