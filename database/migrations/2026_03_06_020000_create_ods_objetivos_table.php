<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ods_objetivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('numero')->unique(); // 1-17
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        // Columna vectorial via raw SQL (Laravel no soporta nativo)
        DB::statement('ALTER TABLE ods_objetivos ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ods_objetivos');
    }
};
