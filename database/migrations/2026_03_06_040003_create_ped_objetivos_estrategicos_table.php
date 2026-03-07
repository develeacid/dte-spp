<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ped_objetivos_estrategicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ped_tema_id')->constrained('ped_temas')->cascadeOnDelete();
            $table->string('clave', 20); // "1.1.1", "1.1.2"
            $table->text('descripcion');
            $table->timestamps();

            $table->unique(['ped_tema_id', 'clave']);
        });

        DB::statement('ALTER TABLE ped_objetivos_estrategicos ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ped_objetivos_estrategicos');
    }
};
