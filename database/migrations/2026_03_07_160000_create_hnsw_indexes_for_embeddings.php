<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $m = config('embedding.hnsw_m', 16);
        $efConstruction = config('embedding.hnsw_ef_construction', 64);

        // ODS
        DB::statement("CREATE INDEX IF NOT EXISTS ods_objetivos_embedding_idx ON ods_objetivos USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ods_metas_embedding_idx ON ods_metas USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");

        // PND
        DB::statement("CREATE INDEX IF NOT EXISTS pnd_ejes_embedding_idx ON pnd_ejes USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS pnd_objetivos_embedding_idx ON pnd_objetivos USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS pnd_estrategias_embedding_idx ON pnd_estrategias USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");

        // PED
        DB::statement("CREATE INDEX IF NOT EXISTS ped_ejes_embedding_idx ON ped_ejes USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_temas_embedding_idx ON ped_temas USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_objetivos_estrategicos_embedding_idx ON ped_objetivos_estrategicos USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_estrategias_embedding_idx ON ped_estrategias USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_lineas_accion_embedding_idx ON ped_lineas_accion USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");

        // Programas Derivados
        DB::statement("CREATE INDEX IF NOT EXISTS programas_derivados_objetivos_embedding_idx ON programas_derivados_objetivos USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ODS
        DB::statement('DROP INDEX IF EXISTS ods_objetivos_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS ods_metas_embedding_idx');

        // PND
        DB::statement('DROP INDEX IF EXISTS pnd_ejes_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS pnd_objetivos_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS pnd_estrategias_embedding_idx');

        // PED
        DB::statement('DROP INDEX IF EXISTS ped_ejes_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS ped_temas_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS ped_objetivos_estrategicos_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS ped_estrategias_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS ped_lineas_accion_embedding_idx');

        // Programas Derivados
        DB::statement('DROP INDEX IF EXISTS programas_derivados_objetivos_embedding_idx');
    }
};
