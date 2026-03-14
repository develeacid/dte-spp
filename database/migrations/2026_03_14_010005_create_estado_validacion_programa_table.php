<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estado_validacion_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')
                ->constrained('programa_presupuestarios')
                ->cascadeOnDelete();
            $table->smallInteger('ejercicio_fiscal');

            // Planeación
            $table->string('planeacion_estado', 20)->default('incompleta');
            $table->jsonb('planeacion_detalle')->nullable();
            $table->timestamp('planeacion_actualizado_at')->nullable();

            // Jurídico (stub en Fase 1)
            $table->string('juridico_estado', 20)->default('no_implementado');
            $table->jsonb('juridico_detalle')->nullable();
            $table->timestamp('juridico_actualizado_at')->nullable();

            // Financiero
            $table->string('financiero_estado', 20)->default('sin_partidas');
            $table->jsonb('financiero_detalle')->nullable();
            $table->timestamp('financiero_actualizado_at')->nullable();

            // Consolidado
            $table->string('consolidado', 20)->default('critico');
            $table->smallInteger('validaciones_completas')->default(0);

            $table->timestamps();

            $table->unique(['programa_presupuestario_id', 'ejercicio_fiscal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estado_validacion_programa');
    }
};
