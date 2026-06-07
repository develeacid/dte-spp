<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recomendaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hallazgo_id')
                ->constrained('hallazgos')
                ->cascadeOnDelete();

            $table->text('descripcion');
            $table->string('prioridad', 10)->default('media');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recomendaciones');
    }
};
