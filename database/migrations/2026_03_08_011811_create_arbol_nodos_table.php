<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arbol_nodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arbol_id')->constrained('arboles')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('arbol_nodos')->cascadeOnDelete();
            $table->string('tipo_nodo', 30);
            $table->text('descripcion');
            $table->foreignId('nodo_origen_id')->nullable()->constrained('arbol_nodos')->nullOnDelete();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index('parent_id');
            $table->index('arbol_id');
            $table->index('nodo_origen_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arbol_nodos');
    }
};
