<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clasificación de la fuente del MV (V2-B10, C-074): enum app-level
     * TipoFuenteMv (externa / administrativa_propia / evaluacion_externa).
     * NULL = legacy sin clasificar (no bloquea guardados, sí genera
     * advertencia B9 en diagnóstico para FIN/PROPÓSITO).
     */
    public function up(): void
    {
        Schema::table('medios_verificacion', function (Blueprint $table) {
            $table->string('tipo_fuente')->nullable()->after('fuente');
        });
    }

    public function down(): void
    {
        Schema::table('medios_verificacion', function (Blueprint $table) {
            $table->dropColumn('tipo_fuente');
        });
    }
};
