<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_periodo_id')
                ->constrained('metas_periodo')->cascadeOnDelete();
            $table->foreignId('indicador_id')
                ->constrained('indicadores')->cascadeOnDelete();
            $table->decimal('resultado', 12, 4)->nullable();
            $table->string('semaforo_calculado', 10)->nullable();
            $table->string('semaforo_ajustado', 10)->nullable();
            $table->text('justificacion_ia')->nullable();
            $table->text('justificacion_final')->nullable();
            $table->string('estado', 20)->default('en_captura');
            $table->jsonb('historial_observaciones')->default('[]');
            $table->timestamp('congelado_at')->nullable();
            $table->foreignId('capturado_por')
                ->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['meta_periodo_id', 'indicador_id']);
            $table->index('estado');
            $table->index('indicador_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avances');
    }
};
