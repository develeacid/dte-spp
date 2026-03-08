<?php

namespace Database\Seeders;

use App\Models\Evaluation\AnexoTransversal;
use Illuminate\Database\Seeder;

class AnexosTransversalesSeeder extends Seeder
{
    public function run(): void
    {
        AnexoTransversal::updateOrCreate(['clave' => 'genero'], [
            'nombre' => 'Igualdad de Género', 'orden' => 1, 'activo' => true,
        ]);
        AnexoTransversal::updateOrCreate(['clave' => 'nna'], [
            'nombre' => 'Niñas, Niños y Adolescentes', 'orden' => 2, 'activo' => true,
        ]);
        AnexoTransversal::updateOrCreate(['clave' => 'cambio_climatico'], [
            'nombre' => 'Cambio Climático', 'orden' => 3, 'activo' => true,
        ]);
        AnexoTransversal::updateOrCreate(['clave' => 'anticorrupcion'], [
            'nombre' => 'Anticorrupción', 'orden' => 4, 'activo' => true,
        ]);
    }
}
