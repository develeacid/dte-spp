<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medios_verificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')
                ->constrained('indicadores')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('fuente')->nullable();
            $table->string('frecuencia', 20)->nullable();
            $table->smallInteger('orden')->default(0);
            $table->timestamps();

            $table->index('indicador_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medios_verificacion');
    }
};
