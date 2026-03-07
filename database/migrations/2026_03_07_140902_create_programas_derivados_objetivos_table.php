<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programas_derivados_objetivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_derivado_id')->constrained('programas_derivados')->cascadeOnDelete();
            $table->string('clave', 20); // "OS.1", "OE.1", etc.
            $table->text('descripcion');
            $table->timestamps();

            $table->unique(['programa_derivado_id', 'clave']);
        });

        // Columna vectorial para búsqueda semántica
        DB::statement('ALTER TABLE programas_derivados_objetivos ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('programas_derivados_objetivos');
    }
};
