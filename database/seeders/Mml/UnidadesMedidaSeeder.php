<?php

namespace Database\Seeders\Mml;

use App\Models\CatalogoUnidadMedida;
use Illuminate\Database\Seeder;

class UnidadesMedidaSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = [
            ['clave' => 'PCT', 'nombre' => 'Porcentaje'],
            ['clave' => 'TASA', 'nombre' => 'Tasa'],
            ['clave' => 'IDX', 'nombre' => 'Índice'],
            ['clave' => 'PROM', 'nombre' => 'Promedio'],
            ['clave' => 'NUM', 'nombre' => 'Número'],
            ['clave' => 'RAZ', 'nombre' => 'Razón'],
            ['clave' => 'PROP', 'nombre' => 'Proporción'],
            ['clave' => 'MNT', 'nombre' => 'Monto'],
        ];

        foreach ($unidades as $unidad) {
            CatalogoUnidadMedida::updateOrCreate(
                ['clave' => $unidad['clave']],
                $unidad
            );
        }
    }
}
