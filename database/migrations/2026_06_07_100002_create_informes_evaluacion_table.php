<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informes_evaluacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evaluacion_externa_id')
                ->unique()
                ->constrained('evaluaciones_externas')
                ->cascadeOnDelete();

            $table->text('resumen_ejecutivo')->nullable();
            $table->text('metodologia')->nullable();
            $table->text('conclusiones')->nullable();
            $table->text('fichas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informes_evaluacion');
    }
};
