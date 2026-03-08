<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avance_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avance_id')
                ->constrained('avances')->cascadeOnDelete();
            $table->foreignId('indicador_variable_id')
                ->constrained('indicador_variables')->cascadeOnDelete();
            $table->decimal('valor', 12, 4);
            $table->decimal('valor_acumulado', 12, 4)->nullable();
            $table->timestamps();

            $table->unique(['avance_id', 'indicador_variable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avance_variables');
    }
};
