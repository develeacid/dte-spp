<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pnd_ejes', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('numero')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE pnd_ejes ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pnd_ejes');
    }
};
