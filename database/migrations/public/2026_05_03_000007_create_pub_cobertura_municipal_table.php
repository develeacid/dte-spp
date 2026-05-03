<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_public')->create('pub_cobertura_municipal', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('ejercicio_fiscal');
            $table->smallInteger('trimestre');
            $table->string('programa_nombre', 255);
            $table->string('municipio_clave', 10); // INEGI 5-digit + reserva
            $table->string('municipio_nombre', 255);
            $table->integer('total_beneficiarios')->default(0);
            $table->integer('total_inscripciones')->default(0);
            $table->decimal('monto_total', 18, 2)->nullable();
            $table->timestamps();

            $table->index(['ejercicio_fiscal', 'trimestre', 'programa_nombre'], 'pub_cobertura_municipal_period_idx');
            $table->index('municipio_clave', 'pub_cobertura_municipal_municipio_idx');
        });

        DB::connection('pgsql_public')->statement(
            'GRANT SELECT ON pub_cobertura_municipal TO '.$this->portalUser()
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_public')->dropIfExists('pub_cobertura_municipal');
    }

    private function portalUser(): string
    {
        return '"'.str_replace('"', '""', config('database.connections.pgsql_public_read.username')).'"';
    }
};
