<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_public')->create('pub_evaluaciones_anuales', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('ejercicio_fiscal');
            $table->string('programa_clave', 50);
            $table->decimal('indice_eficacia', 5, 2)->nullable(); // 0..100
            $table->smallInteger('semaforos_verde')->default(0);
            $table->smallInteger('semaforos_amarillo')->default(0);
            $table->smallInteger('semaforos_rojo')->default(0);
            $table->smallInteger('indicadores_total')->default(0);
            $table->timestamps();

            $table->unique(['ejercicio_fiscal', 'programa_clave'], 'pub_evaluaciones_unique');
        });

        DB::connection('pgsql_public')->statement(
            'GRANT SELECT ON pub_evaluaciones_anuales TO '.$this->portalUser()
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_public')->dropIfExists('pub_evaluaciones_anuales');
    }

    private function portalUser(): string
    {
        return '"'.str_replace('"', '""', config('database.connections.pgsql_public_read.username')).'"';
    }
};
