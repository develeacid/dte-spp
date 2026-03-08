<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicador_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')
                ->constrained('indicadores')->cascadeOnDelete();
            $table->string('simbolo', 5);
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('comportamiento', 20)->nullable();
            $table->foreignId('unidad_medida_id')
                ->nullable()->constrained('catalogo_unidades_medida')->nullOnDelete();
            $table->smallInteger('orden')->default(0);
            $table->timestamps();

            $table->index('indicador_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicador_variables');
    }
};
