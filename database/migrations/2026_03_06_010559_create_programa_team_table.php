<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('programa_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->enum('rol', ['coordinadora', 'coadyuvante'])->default('coadyuvante');
            $table->timestamps();

            $table->unique(['programa_presupuestario_id', 'team_id'], 'pt_programa_team_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programa_team');
    }
};
