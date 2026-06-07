<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DS-07: clasificación de la fuente del MV (externa / administrativa
     * propia / evaluación externa) — V2-B10. Corre contra pgsql_public.
     */
    public function up(): void
    {
        Schema::connection('pgsql_public')->table('pub_medios_verificacion', function (Blueprint $table) {
            $table->string('tipo_fuente')->nullable()->after('fuente');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_public')->table('pub_medios_verificacion', function (Blueprint $table) {
            $table->dropColumn('tipo_fuente');
        });
    }
};
