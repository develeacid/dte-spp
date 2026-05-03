<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_public')->create('pub_alineacion_estrategica', function (Blueprint $table) {
            $table->id();
            $table->string('programa_clave', 50);
            $table->jsonb('ods_metas')->nullable(); // array de claves ODS
            $table->string('pnd_objetivo', 500)->nullable();
            $table->string('ped_eje', 500)->nullable();
            $table->string('ped_objetivo_estrategico', 500)->nullable();
            $table->string('ped_linea_accion', 500)->nullable();
            $table->text('mir_fin_resumen')->nullable();
            $table->text('mir_proposito_resumen')->nullable();
            $table->timestamps();

            $table->index('programa_clave', 'pub_alineacion_clave_idx');
        });

        DB::connection('pgsql_public')->statement(
            'GRANT SELECT ON pub_alineacion_estrategica TO '.$this->portalUser()
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_public')->dropIfExists('pub_alineacion_estrategica');
    }

    private function portalUser(): string
    {
        return '"'.str_replace('"', '""', config('database.connections.pgsql_public_read.username')).'"';
    }
};
