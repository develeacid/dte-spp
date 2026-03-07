<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            DesarrolloSeeder::class,          // Ejecuta los Factories y relaciones
            OdsSeeder::class,
            PedSeeder::class,
        ]);
    }
}
