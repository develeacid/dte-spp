<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Checklist CREMA del Medio de Verificación (V2-B8, C-072): Confiable,
     * Relevante, Económico, Monitoreable, Asequible. Análoga a la CREMAA del
     * indicador (cremaa_validaciones), 1:1 con el MV.
     */
    public function up(): void
    {
        Schema::create('crema_validaciones_mv', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medio_verificacion_id')
                ->unique()
                ->constrained('medios_verificacion')->cascadeOnDelete();
            $table->boolean('confiable')->default(false);
            $table->text('confiable_observacion')->nullable();
            $table->boolean('relevante')->default(false);
            $table->text('relevante_observacion')->nullable();
            $table->boolean('economico')->default(false);
            $table->text('economico_observacion')->nullable();
            $table->boolean('monitoreable')->default(false);
            $table->text('monitoreable_observacion')->nullable();
            $table->boolean('asequible')->default(false);
            $table->text('asequible_observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crema_validaciones_mv');
    }
};
