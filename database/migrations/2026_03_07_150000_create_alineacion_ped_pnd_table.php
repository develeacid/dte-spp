<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('alineacion_ped_pnd')) {
            return;
        }

        Schema::create('alineacion_ped_pnd', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ped_objetivo_estrategico_id')
                ->constrained('ped_objetivos_estrategicos')
                ->cascadeOnDelete();

            $table->foreignId('pnd_objetivo_id')
                ->constrained('pnd_objetivos')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'ped_objetivo_estrategico_id',
                'pnd_objetivo_id',
            ], 'alineacion_ped_pnd_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alineacion_ped_pnd');
    }
};
