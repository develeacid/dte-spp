<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_public')->create('pub_cobertura_geografica', function (Blueprint $table) {
            $table->id();
            $table->string('programa_nombre', 255);
            $table->jsonb('geojson');
            $table->decimal('area_km2', 12, 2)->nullable();
            // PG: array de strings con códigos INEGI municipales
            $table->text('municipios_incluidos')->nullable();
            $table->timestamps();

            $table->index('programa_nombre', 'pub_geografica_programa_idx');
        });

        // Convertir municipios_incluidos a text[] nativo (PG-only)
        DB::connection('pgsql_public')->statement(
            'ALTER TABLE pub_cobertura_geografica ALTER COLUMN municipios_incluidos TYPE text[] USING string_to_array(municipios_incluidos, \',\')'
        );

        DB::connection('pgsql_public')->statement(
            'GRANT SELECT ON pub_cobertura_geografica TO '.$this->portalUser()
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_public')->dropIfExists('pub_cobertura_geografica');
    }

    private function portalUser(): string
    {
        return '"'.str_replace('"', '""', config('database.connections.pgsql_public_read.username')).'"';
    }
};
