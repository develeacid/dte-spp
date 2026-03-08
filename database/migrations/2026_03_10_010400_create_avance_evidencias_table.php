<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avance_evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avance_id')
                ->constrained('avances')->cascadeOnDelete();
            $table->string('nombre_archivo');
            $table->string('ruta_archivo');
            $table->string('mime_type', 50);
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('hash_archivo', 64);
            $table->string('nombre_documento');
            $table->string('area_generadora')->nullable();
            $table->date('fecha_documento')->nullable();
            $table->foreignId('subido_por')
                ->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('avance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avance_evidencias');
    }
};
