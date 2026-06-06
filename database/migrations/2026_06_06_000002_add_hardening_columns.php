<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columnas opcionales del temario MIR (V2-A5/A7/A11). Todas NULLABLE:
     * son metadatos opcionales, no campos load-bearing (contrastar con Task 2,
     * que las hizo NOT NULL con defaults/backfill).
     */
    public function up(): void
    {
        Schema::table('indicadores', function (Blueprint $table) {
            // V2-A5: año de la línea base
            $table->smallInteger('linea_base_anio')->nullable()->after('linea_base');
        });

        Schema::table('medios_verificacion', function (Blueprint $table) {
            // V2-A7: organismo responsable + URL del medio de verificación
            $table->string('organismo')->nullable()->after('fuente');
            $table->string('url')->nullable()->after('organismo');
        });

        Schema::table('indicador_variables', function (Blueprint $table) {
            // V2-A11: fuente de la variable
            $table->string('fuente')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('indicadores', function (Blueprint $table) {
            $table->dropColumn('linea_base_anio');
        });

        Schema::table('medios_verificacion', function (Blueprint $table) {
            $table->dropColumn(['organismo', 'url']);
        });

        Schema::table('indicador_variables', function (Blueprint $table) {
            $table->dropColumn('fuente');
        });
    }
};
