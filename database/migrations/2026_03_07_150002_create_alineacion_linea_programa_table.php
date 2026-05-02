<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('alineacion_linea_programa')) {
            return;
        }

        Schema::create('alineacion_linea_programa', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ped_linea_accion_id')
                ->constrained('ped_lineas_accion')
                ->cascadeOnDelete();

            $table->foreignId('programa_derivado_objetivo_id')
                ->constrained('programas_derivados_objetivos')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'ped_linea_accion_id',
                'programa_derivado_objetivo_id',
            ], 'alineacion_linea_programa_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alineacion_linea_programa');
    }
};
