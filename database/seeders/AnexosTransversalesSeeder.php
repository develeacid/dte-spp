<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnexosTransversalesSeeder extends Seeder
{
    public function run(): void
    {
        $anexos = [
            ['id' => 1, 'clave' => 'genero', 'nombre' => 'Igualdad de Género', 'orden' => 1, 'activo' => true],
            ['id' => 2, 'clave' => 'nna', 'nombre' => 'Niñas, Niños y Adolescentes', 'orden' => 2, 'activo' => true],
            ['id' => 3, 'clave' => 'cambio_climatico', 'nombre' => 'Cambio Climático', 'orden' => 3, 'activo' => true],
            ['id' => 4, 'clave' => 'anticorrupcion', 'nombre' => 'Anticorrupción', 'orden' => 4, 'activo' => true],
        ];

        DB::table('anexos_transversales')->upsert(
            $anexos,
            ['id'],
            ['clave', 'nombre', 'orden', 'activo']
        );
    }
}
