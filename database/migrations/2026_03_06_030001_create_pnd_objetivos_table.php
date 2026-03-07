<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pnd_objetivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnd_eje_id')->constrained()->cascadeOnDelete();
            $table->string('clave', 20)->unique();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE pnd_objetivos ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pnd_objetivos');
    }
};
