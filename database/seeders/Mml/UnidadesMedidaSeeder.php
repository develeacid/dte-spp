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

        // Orden de ejecución en migrate:fresh --seed:
        //   1) migración 2026_06_06_000001_harden_mir_fields corre sobre el
        //      catálogo VACÍO y crea 'ND' con un id auto-secuencial (~2, porque
        //      el setval sobre tabla vacía deja next=2).
        //   2) este seeder upserta 9 filas con ids EXPLÍCITOS 1..9 y conflict
        //      target ['clave'].
        // En BD fresca ese ND@~2 no conflictúa por clave con la fila {id:2,
        // clave:'TASA'} pero SÍ colisiona en PK (id=2) → unique violation.
        //
        // Por eso eliminamos primero el ND "stray" (id<>9) SIEMPRE que ningún
        // indicador lo referencie. En una BD vieja (prod) donde ya hay datos, su
        // id es >9 y/o está referenciado: lo dejamos intacto y el upsert por
        // clave solo actualizará su nombre (sin colisión de PK).
        DB::table('catalogo_unidades_medida')
            ->where('clave', 'ND')
            ->where('id', '<>', 9)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('indicadores')
                    ->whereColumn('indicadores.unidad_medida_id', 'catalogo_unidades_medida.id');
            })
            ->delete();

        DB::table('catalogo_unidades_medida')->upsert(
            $unidades,
            ['clave'],
            ['nombre']
        );

        // Los ids explícitos del upsert no avanzan la secuencia de Postgres, así
        // que un insert por secuencia futuro (p.ej. el firstOrCreate de 'ND' del
        // hook MIR) colisionaría. Realineamos la secuencia al MAX(id) actual.
        DB::statement("SELECT setval(pg_get_serial_sequence('catalogo_unidades_medida', 'id'), (SELECT GREATEST(COALESCE(MAX(id), 1), 1) FROM catalogo_unidades_medida))");
    }
}
