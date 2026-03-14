<?php

namespace Database\Seeders;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class JuridicoPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear los 4 permisos jurídicos
        $permisosJuridicos = [
            SystemPermission::GESTIONAR_SUSTENTO_LEGAL,
            SystemPermission::VALIDAR_SUSTENTO_LEGAL,
            SystemPermission::VER_SUSTENTO_LEGAL,
            SystemPermission::GESTIONAR_REGLAS_OPERACION,
        ];

        foreach ($permisosJuridicos as $permiso) {
            Permission::findOrCreate($permiso->value, 'web');
        }

        // Crear rol Analista Jurídico con todos los permisos jurídicos + ver datos financieros
        $analistaJuridico = Role::findOrCreate(SystemRole::ANALISTA_JURIDICO->value, 'web');
        $analistaJuridico->givePermissionTo(array_merge(
            array_map(fn ($p) => $p->value, $permisosJuridicos),
            [SystemPermission::VER_DATOS_FINANCIEROS->value]
        ));

        // Admin recibe todos los permisos jurídicos
        $admin = Role::findOrCreate(SystemRole::ADMIN->value, 'web');
        $admin->givePermissionTo(array_map(fn ($p) => $p->value, $permisosJuridicos));

        // Planeador y Analista Financiero solo pueden ver sustento legal
        foreach ([SystemRole::PLANEADOR, SystemRole::ANALISTA_FINANCIERO] as $role) {
            $rol = Role::findOrCreate($role->value, 'web');
            $rol->givePermissionTo(SystemPermission::VER_SUSTENTO_LEGAL->value);
        }
    }
}
