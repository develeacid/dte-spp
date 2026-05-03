<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_public')->create('pub_desagregacion_demografica', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('ejercicio_fiscal');
            $table->string('programa_nombre', 255);
            $table->string('municipio_clave', 10)->nullable(); // null = nivel estatal
            $table->string('dimension', 50); // sexo / grupo_edad / discapacidad / pueblo
            $table->string('categoria', 100);
            $table->integer('total_beneficiarios')->default(0);
            $table->decimal('porcentaje', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['ejercicio_fiscal', 'programa_nombre', 'dimension'], 'pub_desagregacion_idx');
        });

        DB::connection('pgsql_public')->statement(
            'GRANT SELECT ON pub_desagregacion_demografica TO '.$this->portalUser()
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_public')->dropIfExists('pub_desagregacion_demografica');
    }

    private function portalUser(): string
    {
        return '"'.str_replace('"', '""', config('database.connections.pgsql_public_read.username')).'"';
    }
};
