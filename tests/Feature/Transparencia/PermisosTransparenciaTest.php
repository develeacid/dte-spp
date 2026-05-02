<?php

namespace Tests\Feature\Transparencia;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TransparenciaPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermisosTransparenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_rol_rda_con_permiso_aprobar(): void
    {
        $this->seed(TransparenciaPermissionsSeeder::class);

        $rol = Role::where('name', SystemRole::RESPONSABLE_DATOS_ABIERTOS->value)->first();
        $this->assertNotNull($rol);

        $permiso = Permission::where('name', SystemPermission::APROBAR_DATOS_ABIERTOS->value)->first();
        $this->assertNotNull($permiso);

        $this->assertTrue($rol->hasPermissionTo($permiso));
    }

    public function test_no_asigna_aprobar_datos_a_admin(): void
    {
        // Reproducir el orden real de Fase0PrerequisitosSeeder:
        // RolesAndPermissionsSeeder primero (admin recibe Permission::all() — sin el
        // permiso de transparencia porque aún no existe), luego TransparenciaPermissionsSeeder
        // (crea aprobar_datos_abiertos pero deliberadamente NO lo asigna a admin).
        // Esto verifica la segregación EFECTIVA en producción, no solo el aislamiento del SUT.
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TransparenciaPermissionsSeeder::class);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::where('name', SystemRole::ADMIN->value)->first();
        $this->assertNotNull($admin, 'El rol admin debe existir');

        $this->assertFalse(
            $admin->hasPermissionTo(SystemPermission::APROBAR_DATOS_ABIERTOS->value),
            'Admin NO debe tener aprobar_datos_abiertos (segregación efectiva post-RolesAndPermissionsSeeder)'
        );
    }

    public function test_es_idempotente(): void
    {
        $this->seed(TransparenciaPermissionsSeeder::class);
        $this->seed(TransparenciaPermissionsSeeder::class);

        $this->assertEquals(
            1,
            Permission::where('name', SystemPermission::APROBAR_DATOS_ABIERTOS->value)->count()
        );
        $this->assertEquals(
            1,
            Role::where('name', SystemRole::RESPONSABLE_DATOS_ABIERTOS->value)->count()
        );
    }

    public function test_admin_tiene_ver_y_gestionar_pero_no_aprobar(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TransparenciaPermissionsSeeder::class);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::where('name', SystemRole::ADMIN->value)->first();
        $this->assertTrue($admin->hasPermissionTo(SystemPermission::VER_DATASETS_ABIERTOS->value));
        $this->assertTrue($admin->hasPermissionTo(SystemPermission::GESTIONAR_DATASET_ABIERTO->value));
        $this->assertFalse($admin->hasPermissionTo(SystemPermission::APROBAR_DATOS_ABIERTOS->value));
    }

    public function test_rda_tiene_los_tres_permisos(): void
    {
        $this->seed(TransparenciaPermissionsSeeder::class);

        $rda = Role::where('name', SystemRole::RESPONSABLE_DATOS_ABIERTOS->value)->first();
        $this->assertTrue($rda->hasPermissionTo(SystemPermission::APROBAR_DATOS_ABIERTOS->value));
        $this->assertTrue($rda->hasPermissionTo(SystemPermission::VER_DATASETS_ABIERTOS->value));
        $this->assertTrue($rda->hasPermissionTo(SystemPermission::GESTIONAR_DATASET_ABIERTO->value));
    }

    public function test_planeador_tiene_ver_y_gestionar(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TransparenciaPermissionsSeeder::class);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $planeador = Role::where('name', SystemRole::PLANEADOR->value)->first();
        $this->assertTrue($planeador->hasPermissionTo(SystemPermission::VER_DATASETS_ABIERTOS->value));
        $this->assertTrue($planeador->hasPermissionTo(SystemPermission::GESTIONAR_DATASET_ABIERTO->value));
        $this->assertFalse($planeador->hasPermissionTo(SystemPermission::APROBAR_DATOS_ABIERTOS->value));
    }
}
