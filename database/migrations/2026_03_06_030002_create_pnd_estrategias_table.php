<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pnd_estrategias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnd_objetivo_id')->constrained()->cascadeOnDelete();
            $table->string('clave', 30)->unique();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE pnd_estrategias ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pnd_estrategias');
    }
};
