<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_public')->create('pub_medios_verificacion', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('ejercicio_fiscal');
            $table->string('programa_clave', 50);
            $table->string('mir_nivel', 50); // fin / proposito / componente / actividad
            $table->string('indicador_nombre', 255);
            $table->string('mv_nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('fuente', 255)->nullable();
            $table->string('organismo', 255)->nullable();
            $table->text('url')->nullable();
            $table->string('frecuencia', 50)->nullable();
            $table->timestamps();

            $table->index(['ejercicio_fiscal', 'programa_clave'], 'pub_mv_ejercicio_clave_idx');
        });

        DB::connection('pgsql_public')->statement(
            'GRANT SELECT ON pub_medios_verificacion TO '.$this->portalUser()
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_public')->dropIfExists('pub_medios_verificacion');
    }

    private function portalUser(): string
    {
        return '"'.str_replace('"', '""', config('database.connections.pgsql_public_read.username')).'"';
    }
};
