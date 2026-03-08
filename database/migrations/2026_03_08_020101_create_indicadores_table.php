<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mir_nivel_id')
                ->constrained('mir_niveles')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('formula_texto')->nullable();
            $table->string('tipo', 20);
            $table->string('dimension', 20);
            $table->string('frecuencia', 20);
            $table->string('sentido', 20)->nullable();
            $table->decimal('linea_base', 12, 4)->nullable();
            $table->decimal('meta', 12, 4)->nullable();
            $table->decimal('rango_verde_min', 8, 2)->nullable();
            $table->decimal('rango_verde_max', 8, 2)->nullable();
            $table->decimal('rango_amarillo_min', 8, 2)->nullable();
            $table->decimal('rango_amarillo_max', 8, 2)->nullable();
            $table->decimal('rango_rojo_min', 8, 2)->nullable();
            $table->decimal('rango_rojo_max', 8, 2)->nullable();
            $table->foreignId('unidad_medida_id')
                ->nullable()->constrained('catalogo_unidades_medida')->nullOnDelete();
            $table->smallInteger('orden')->default(0);
            $table->timestamps();

            $table->index('mir_nivel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicadores');
    }
};
