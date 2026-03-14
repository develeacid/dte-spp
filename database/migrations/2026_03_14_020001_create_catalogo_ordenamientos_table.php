<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_ordenamientos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('nivel_jerarquia', 30);
            $table->string('abreviatura', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->smallInteger('orden')->default(0);
            $table->timestamps();

            $table->index('nivel_jerarquia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_ordenamientos');
    }
};
