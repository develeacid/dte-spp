<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arboles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')->constrained('programa_presupuestarios')->cascadeOnDelete();
            $table->string('tipo', 20);
            $table->timestamps();

            $table->unique(['programa_presupuestario_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arboles');
    }
};
