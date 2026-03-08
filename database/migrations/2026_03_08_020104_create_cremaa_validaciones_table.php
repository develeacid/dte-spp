<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cremaa_validaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')
                ->constrained('indicadores')->cascadeOnDelete();
            $table->boolean('claro')->default(false);
            $table->text('claro_observacion')->nullable();
            $table->boolean('relevante')->default(false);
            $table->text('relevante_observacion')->nullable();
            $table->boolean('economico')->default(false);
            $table->text('economico_observacion')->nullable();
            $table->boolean('monitoreable')->default(false);
            $table->text('monitoreable_observacion')->nullable();
            $table->boolean('adecuado')->default(false);
            $table->text('adecuado_observacion')->nullable();
            $table->boolean('aportante')->default(false);
            $table->text('aportante_observacion')->nullable();
            $table->timestamps();

            $table->index('indicador_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cremaa_validaciones');
    }
};
