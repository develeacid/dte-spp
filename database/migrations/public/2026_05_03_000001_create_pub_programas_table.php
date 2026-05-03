<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_public')->create('pub_programas', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('ejercicio_fiscal');
            $table->string('programa_clave', 50);
            $table->string('programa_nombre', 255);
            $table->string('unidad_responsable', 255);
            $table->string('modalidad', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['ejercicio_fiscal', 'programa_clave'], 'pub_programas_ejercicio_clave_idx');
        });

        DB::connection('pgsql_public')->statement(
            'GRANT SELECT ON pub_programas TO '.$this->portalUser()
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_public')->dropIfExists('pub_programas');
    }

    private function portalUser(): string
    {
        return '"'.str_replace('"', '""', config('database.connections.pgsql_public_read.username')).'"';
    }
};
