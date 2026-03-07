<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ped_lineas_accion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ped_estrategia_id')->constrained()->cascadeOnDelete();
            $table->string('clave', 40); // "1.1.1.1.1", "1.1.1.1.2"
            $table->text('descripcion');
            $table->timestamps();

            $table->unique(['ped_estrategia_id', 'clave']);
        });

        DB::statement('ALTER TABLE ped_lineas_accion ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ped_lineas_accion');
    }
};
