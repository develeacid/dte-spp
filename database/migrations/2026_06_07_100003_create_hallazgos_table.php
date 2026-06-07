<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hallazgos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('informe_evaluacion_id')
                ->constrained('informes_evaluacion')
                ->cascadeOnDelete();

            $table->text('descripcion');
            $table->text('evidencia_url')->nullable();
            $table->string('severidad', 10)->default('media');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hallazgos');
    }
};
