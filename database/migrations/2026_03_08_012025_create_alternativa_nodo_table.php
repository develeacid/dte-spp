<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alternativa_nodo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alternativa_id')->constrained('alternativas')->cascadeOnDelete();
            $table->foreignId('arbol_nodo_id')->constrained('arbol_nodos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['alternativa_id', 'arbol_nodo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alternativa_nodo');
    }
};
