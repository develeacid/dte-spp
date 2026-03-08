<?php

namespace Database\Seeders;

use Database\Seeders\Cascade\PedSeeder;
use Database\Seeders\Cascade\ProgramasDerivadosSeeder;
use Database\Seeders\Mml\OdsSeeder;
use Database\Seeders\Mml\PndSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class, // Esencial generar permisos antes que usuarios
            AdminUserSeeder::class,           // Usuario administrador real
            DesarrolloSeeder::class,          // Ejecuta los Factories y relaciones
            OdsSeeder::class,                 // Catálogo ODS Agenda 2030
            PndSeeder::class,                 // Catálogo Plan Nacional de Desarrollo
            PedSeeder::class,
            ProgramasDerivadosSeeder::class,  // Programas derivados del PED
            AnexosTransversalesSeeder::class, // Catálogo Anexos Transversales
        ]);
    }
}
