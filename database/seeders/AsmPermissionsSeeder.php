<?php

namespace Database\Seeders;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AsmPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            SystemPermission::VER_ASM,
            SystemPermission::GESTIONAR_ASM,
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso->value, 'web');
        }

        // Todos los roles reciben ver_asm (lectura amplia para auditoría y consistencia)
        foreach (SystemRole::cases() as $role) {
            $rol = Role::findOrCreate($role->value, 'web');
            $rol->givePermissionTo(SystemPermission::VER_ASM->value);
        }

        // gestionar_asm solo para planeador, operador y admin
        $rolesGestion = [
            SystemRole::PLANEADOR,
            SystemRole::OPERADOR,
            SystemRole::ADMIN,
        ];

        foreach ($rolesGestion as $role) {
            $rol = Role::findOrCreate($role->value, 'web');
            $rol->givePermissionTo(SystemPermission::GESTIONAR_ASM->value);
        }
    }
}
