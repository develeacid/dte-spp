<?php

namespace Database\Seeders\Mml;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadesMedidaSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = [
            ['id' => 1, 'clave' => 'PCT', 'nombre' => 'Porcentaje'],
            ['id' => 2, 'clave' => 'TASA', 'nombre' => 'Tasa'],
            ['id' => 3, 'clave' => 'IDX', 'nombre' => 'Índice'],
            ['id' => 4, 'clave' => 'PROM', 'nombre' => 'Promedio'],
            ['id' => 5, 'clave' => 'NUM', 'nombre' => 'Número'],
            ['id' => 6, 'clave' => 'RAZ', 'nombre' => 'Razón'],
            ['id' => 7, 'clave' => 'PROP', 'nombre' => 'Proporción'],
            ['id' => 8, 'clave' => 'MNT', 'nombre' => 'Monto'],
            ['id' => 9, 'clave' => 'ND', 'nombre' => 'No definida'],
        ];

        // Conflict target por 'clave' (UNIQUE): la migración harden_mir_fields puede haber
        // creado 'ND' con id auto-secuencial distinto de 9; un upsert por id duplicaría la clave.
        DB::table('catalogo_unidades_medida')->upsert(
            $unidades,
            ['clave'],
            ['nombre']
        );
    }
}
