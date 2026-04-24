<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('programa_presupuestario_id')
                ->constrained('programa_presupuestarios')
                ->restrictOnDelete();

            $table->foreignId('evaluacion_id')
                ->nullable()
                ->constrained('evaluaciones_programa')
                ->nullOnDelete();

            $table->text('descripcion_aspecto');
            $table->text('accion_mejora');
            $table->string('tipo_plazo', 16);
            $table->string('tipo_accion', 32);

            $table->foreignId('responsable_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('area_responsable', 255);
            $table->date('fecha_compromiso');
            $table->date('fecha_cumplimiento')->nullable();
            $table->unsignedTinyInteger('porcentaje_avance')->default(0);
            $table->text('observacion_ultimo_avance')->nullable();
            $table->string('status', 16)->default('pendiente');
            $table->string('evidencia_url', 2048)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['programa_presupuestario_id', 'status'], 'asms_programa_status_idx');
            $table->index(['responsable_id', 'status'], 'asms_responsable_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asms');
    }
};
