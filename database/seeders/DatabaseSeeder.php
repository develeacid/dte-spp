<?php

namespace Database\Seeders;

use Database\Seeders\Cascade\AlineacionesSeeder;
use Database\Seeders\Cascade\PedSeeder;
use Database\Seeders\Cascade\ProgramasDerivadosSeeder;
use Database\Seeders\Mml\OdsSeeder;
use Database\Seeders\Mml\PndSeeder;
use Database\Seeders\Mml\UnidadesMedidaSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            Fase0PrerequisitosSeeder::class,
            // Catálogos
            OdsSeeder::class,
            PndSeeder::class,
            UnidadesMedidaSeeder::class,
            PedSeeder::class,
            ProgramasDerivadosSeeder::class,
            AlineacionesSeeder::class,
            AnexosTransversalesSeeder::class,
            CatalogoOrdenamientosSeeder::class,
            Fase1PlaneacionMmlSeeder::class,
            Fase2PresupuestoSeeder::class,
        ]);
    }
}
