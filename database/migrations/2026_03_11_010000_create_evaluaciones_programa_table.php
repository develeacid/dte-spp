<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')
                ->constrained('programa_presupuestarios')->cascadeOnDelete();
            $table->unsignedSmallInteger('ejercicio_fiscal');
            $table->decimal('indice_eficacia', 8, 4)->nullable();
            $table->jsonb('desglose_niveles')->nullable();
            $table->jsonb('conteo_semaforos')->nullable();
            $table->unsignedInteger('indicadores_evaluados')->default(0);
            $table->unsignedInteger('indicadores_no_evaluados')->default(0);
            $table->jsonb('configuracion_calculo')->nullable();
            $table->text('analisis_ia')->nullable();
            $table->foreignId('calculado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['programa_presupuestario_id', 'ejercicio_fiscal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_programa');
    }
};
