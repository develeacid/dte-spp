<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas_periodo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')
                ->constrained('indicadores')
                ->cascadeOnDelete();
            $table->smallInteger('periodo');
            $table->decimal('meta_periodo', 12, 4);
            $table->integer('ejercicio_fiscal');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['indicador_id', 'periodo', 'ejercicio_fiscal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas_periodo');
    }
};
