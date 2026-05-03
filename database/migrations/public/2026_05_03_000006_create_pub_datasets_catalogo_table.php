<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_public')->create('pub_datasets_catalogo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique(); // p.ej. DS-01, DS-G02
            $table->string('titulo', 255);
            $table->text('descripcion');
            $table->date('fecha_publicacion')->nullable();
            $table->string('frecuencia_actualizacion', 100)->nullable();
            $table->string('url_csv', 500)->nullable();
            $table->string('url_json', 500)->nullable();
            $table->bigInteger('total_registros')->nullable();
            $table->timestamps();
        });

        DB::connection('pgsql_public')->statement(
            'GRANT SELECT ON pub_datasets_catalogo TO '.$this->portalUser()
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_public')->dropIfExists('pub_datasets_catalogo');
    }

    private function portalUser(): string
    {
        return '"'.str_replace('"', '""', config('database.connections.pgsql_public_read.username')).'"';
    }
};
