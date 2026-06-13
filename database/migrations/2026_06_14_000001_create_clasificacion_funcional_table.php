<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clasificacion_funcional', function (Blueprint $table) {
            $table->id();
            // nivel: finalidad | funcion | subfuncion (Clasificación Funcional del Gasto CONAC)
            $table->string('nivel', 12);
            $table->string('clave', 3);
            $table->string('nombre');
            $table->foreignId('padre_id')->nullable()->constrained('clasificacion_funcional')->nullOnDelete();
            $table->timestamps();

            $table->unique(['nivel', 'clave']);
            $table->index('padre_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clasificacion_funcional');
    }
};
