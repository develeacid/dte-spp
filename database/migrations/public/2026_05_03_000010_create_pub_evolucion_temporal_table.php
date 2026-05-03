<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_public')->create('pub_evolucion_temporal', function (Blueprint $table) {
            $table->id();
            $table->string('programa_nombre', 255);
            $table->smallInteger('ejercicio_fiscal');
            $table->smallInteger('trimestre');
            $table->integer('total_beneficiarios')->default(0);
            $table->timestamps();

            $table->index(['programa_nombre', 'ejercicio_fiscal', 'trimestre'], 'pub_evolucion_idx');
        });

        DB::connection('pgsql_public')->statement(
            'GRANT SELECT ON pub_evolucion_temporal TO '.$this->portalUser()
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_public')->dropIfExists('pub_evolucion_temporal');
    }

    private function portalUser(): string
    {
        return '"'.str_replace('"', '""', config('database.connections.pgsql_public_read.username')).'"';
    }
};
