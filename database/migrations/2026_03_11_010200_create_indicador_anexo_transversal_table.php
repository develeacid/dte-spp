<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicador_anexo_transversal', function (Blueprint $table) {
            $table->foreignId('indicador_id')->constrained('indicadores')->cascadeOnDelete();
            $table->foreignId('anexo_transversal_id')->constrained('anexos_transversales')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['indicador_id', 'anexo_transversal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicador_anexo_transversal');
    }
};
