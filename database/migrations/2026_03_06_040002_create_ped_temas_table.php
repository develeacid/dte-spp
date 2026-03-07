<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ped_temas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ped_eje_id')->constrained()->cascadeOnDelete();
            $table->string('numero', 10); // "1.1", "1.2"
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->unique(['ped_eje_id', 'numero']);
        });

        DB::statement('ALTER TABLE ped_temas ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ped_temas');
    }
};
