<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_public')->table('pub_evaluaciones_anuales', function (Blueprint $table) {
            $table->smallInteger('semaforos_rojo_alto')->default(0)->after('semaforos_rojo');
        });

        // El GRANT SELECT original se concedió a nivel de tabla
        // (GRANT SELECT ON pub_evaluaciones_anuales), por lo que cubre las
        // columnas nuevas automáticamente. No se requiere GRANT adicional.
    }

    public function down(): void
    {
        Schema::connection('pgsql_public')->table('pub_evaluaciones_anuales', function (Blueprint $table) {
            $table->dropColumn('semaforos_rojo_alto');
        });
    }
};
