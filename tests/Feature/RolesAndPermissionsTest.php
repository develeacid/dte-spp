<?php

namespace Tests\Feature;

use App\Enums\SystemRole;
use App\Enums\SystemPermission;
use App\Models\User;
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

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
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

    public function test_admin_tiene_todos_los_permisos(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ADMIN->value);

        foreach (SystemPermission::cases() as $permiso) {
            $this->assertTrue($user->hasPermissionTo($permiso->value));
        }
    }
}
