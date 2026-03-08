<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alternativas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')->constrained('programa_presupuestarios')->cascadeOnDelete();
            $table->string('nombre');
            $table->boolean('seleccionada')->default(false);
            $table->text('justificacion_seleccion')->nullable();
            $table->timestamps();

            $table->index('programa_presupuestario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alternativas');
    }
};
