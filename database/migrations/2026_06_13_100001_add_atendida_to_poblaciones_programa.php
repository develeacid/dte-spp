<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poblaciones_programa', function (Blueprint $table) {
            // Población atendida REAL del Propósito, sincronizada desde geobase
            // (endpoint atendida-proposito). Completa el embudo planeado→real.
            // Nullable: NULL = aún no sincronizado. NO entra en chk_embudo_logico
            // porque la atendida puede superar al objetivo (sobrecumplimiento).
            $table->unsignedInteger('atendida_cantidad')->nullable()->after('objetivo_justificacion');
            $table->timestamp('atendida_sync_at')->nullable()->after('atendida_cantidad');
        });
    }

    public function down(): void
    {
        Schema::table('poblaciones_programa', function (Blueprint $table) {
            $table->dropColumn(['atendida_cantidad', 'atendida_sync_at']);
        });
    }
};
