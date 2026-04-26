<?php

namespace Database\Seeders;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PresupuestoPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear los 4 permisos financieros
        $permisosFinancieros = [
            SystemPermission::GESTIONAR_PRESUPUESTO,
            SystemPermission::CAPTURAR_AVANCE_FINANCIERO,
            SystemPermission::VER_DATOS_FINANCIEROS,
            SystemPermission::EXPORTAR_CUENTA_PUBLICA,
        ];

        foreach ($permisosFinancieros as $permiso) {
            Permission::findOrCreate($permiso->value, 'web');
        }

        // Crear rol Analista Financiero con todos los permisos financieros
        $analistaFinanciero = Role::findOrCreate(SystemRole::ANALISTA_FINANCIERO->value, 'web');
        $analistaFinanciero->givePermissionTo(array_map(fn ($p) => $p->value, $permisosFinancieros));
        $analistaFinanciero->givePermissionTo(SystemPermission::EXPORTAR_REPORTES->value);

        // Admin recibe todos los permisos financieros
        $admin = Role::findOrCreate(SystemRole::ADMIN->value, 'web');
        $admin->givePermissionTo(array_map(fn ($p) => $p->value, $permisosFinancieros));

        // Planeador solo puede ver datos y exportar cuenta pública
        $planeador = Role::findOrCreate(SystemRole::PLANEADOR->value, 'web');
        $planeador->givePermissionTo([
            SystemPermission::VER_DATOS_FINANCIEROS->value,
            SystemPermission::EXPORTAR_CUENTA_PUBLICA->value,
        ]);
    }
}
