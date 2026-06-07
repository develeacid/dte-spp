<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill idempotente (hay datos reales en prod VPS)
        DB::table('mir_niveles')->whereNull('resumen_narrativo')->update(['resumen_narrativo' => '']);
        DB::table('indicadores')->whereNull('formula_texto')->update(['formula_texto' => '']);
        DB::table('indicadores')->where(function ($q) {
            $q->whereNull('sentido')->orWhere('sentido', 'regular');
        })->update(['sentido' => 'ascendente']);

        // Resync de la secuencia: el UnidadesMedidaSeeder upserta ids explícitos
        // (1..8) sin avanzar la secuencia de Postgres, así que un insert por
        // secuencia colisionaría. Realineamos antes de insertar 'ND'.
        DB::statement("SELECT setval(pg_get_serial_sequence('catalogo_unidades_medida', 'id'), COALESCE((SELECT MAX(id) FROM catalogo_unidades_medida), 1))");

        $unidadId = DB::table('catalogo_unidades_medida')->where('clave', 'ND')->value('id');
        if (! $unidadId) {
            $unidadId = DB::table('catalogo_unidades_medida')->insertGetId([
                'clave' => 'ND',
                'nombre' => 'No definida',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('indicadores')->whereNull('unidad_medida_id')->update(['unidad_medida_id' => $unidadId]);

        DB::statement("ALTER TABLE mir_niveles ALTER COLUMN resumen_narrativo SET DEFAULT '', ALTER COLUMN resumen_narrativo SET NOT NULL");
        DB::statement("ALTER TABLE indicadores ALTER COLUMN formula_texto SET DEFAULT '', ALTER COLUMN formula_texto SET NOT NULL");
        DB::statement("ALTER TABLE indicadores ALTER COLUMN sentido SET DEFAULT 'ascendente', ALTER COLUMN sentido SET NOT NULL");
        DB::statement('ALTER TABLE indicadores ALTER COLUMN unidad_medida_id SET NOT NULL');
    }

    public function down(): void
    {
        // Revierte a nullable y quita defaults. No revierte datos backfilled
        // ni borra el registro ND (decisión load-bearing del sprint).
        DB::statement('ALTER TABLE mir_niveles ALTER COLUMN resumen_narrativo DROP NOT NULL, ALTER COLUMN resumen_narrativo DROP DEFAULT');
        DB::statement('ALTER TABLE indicadores ALTER COLUMN formula_texto DROP NOT NULL, ALTER COLUMN formula_texto DROP DEFAULT');
        DB::statement('ALTER TABLE indicadores ALTER COLUMN sentido DROP NOT NULL, ALTER COLUMN sentido DROP DEFAULT');
        DB::statement('ALTER TABLE indicadores ALTER COLUMN unidad_medida_id DROP NOT NULL');
    }
};
