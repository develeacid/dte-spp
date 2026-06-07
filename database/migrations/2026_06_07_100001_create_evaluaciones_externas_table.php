<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_externas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('programa_presupuestario_id')
                ->constrained('programa_presupuestarios')
                ->restrictOnDelete();

            $table->smallInteger('ejercicio_fiscal');
            $table->string('tipo', 30);
            $table->string('evaluador_externo');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('estado', 15)->default('en_proceso');

            $table->foreignId('evaluacion_programa_id')
                ->nullable()
                ->constrained('evaluaciones_programa')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['programa_presupuestario_id', 'ejercicio_fiscal'], 'eval_externas_programa_ejercicio_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_externas');
    }
};
