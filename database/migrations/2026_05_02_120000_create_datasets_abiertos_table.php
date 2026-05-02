<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datasets_abiertos', function (Blueprint $table) {
            $table->id();
            $table->string('dataset_clave', 20);
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('sistema_origen', 20);
            $table->string('periodo', 7)->nullable();
            $table->string('status', 20)->default('borrador');
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_en')->nullable();
            $table->timestamp('publicado_en')->nullable();
            $table->char('hash_sha256', 64)->nullable();
            $table->string('ruta_archivo', 500)->nullable();
            $table->jsonb('dcat_metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'sistema_origen']);
        });

        // PG 15+: NULLS NOT DISTINCT — required so (DS-00, NULL) collides con (DS-00, NULL).
        // NO reemplazar con $table->unique([...]): Laravel emite NULLS DISTINCT (default SQL),
        // que permite filas DS-00/null duplicadas y rompe updateOrCreate(periodo=null) en
        // PoliticaClasificacionSeeder + RegistrarAcuerdoLegalCommand. Ver test
        // test_aplica_unique_dataset_clave_periodo como guard de regresión.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX datasets_abiertos_clave_periodo_unique
            ON datasets_abiertos (dataset_clave, periodo)
            NULLS NOT DISTINCT
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE datasets_abiertos
            ADD CONSTRAINT datasets_abiertos_status_check
            CHECK (status IN ('borrador','revision','aprobado','publicado','retirado'))
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('datasets_abiertos');
    }
};
