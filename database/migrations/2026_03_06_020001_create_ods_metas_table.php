<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ods_metas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ods_objetivo_id')->constrained()->cascadeOnDelete();
            $table->string('clave', 10)->unique(); // Ej: "1.1", "13.2"
            $table->text('descripcion');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE ods_metas ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ods_metas');
    }
};
