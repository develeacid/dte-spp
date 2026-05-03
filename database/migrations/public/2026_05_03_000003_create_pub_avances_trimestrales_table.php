<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_public')->create('pub_avances_trimestrales', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('ejercicio_fiscal');
            $table->smallInteger('trimestre'); // 1..4
            $table->string('programa_clave', 50);
            $table->string('mir_nivel', 50);
            $table->string('indicador_nombre', 255);
            $table->decimal('meta_trimestral', 18, 4)->nullable();
            $table->decimal('resultado', 18, 4)->nullable();
            $table->string('semaforo', 20)->nullable(); // verde/amarillo/rojo
            $table->boolean('tiene_justificacion')->default(false);
            $table->timestamps();

            $table->index(['ejercicio_fiscal', 'trimestre', 'programa_clave'], 'pub_avances_period_clave_idx');
        });

        DB::connection('pgsql_public')->statement(
            'GRANT SELECT ON pub_avances_trimestrales TO '.$this->portalUser()
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_public')->dropIfExists('pub_avances_trimestrales');
    }

    private function portalUser(): string
    {
        return '"'.str_replace('"', '""', config('database.connections.pgsql_public_read.username')).'"';
    }
};
