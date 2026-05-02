<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('alineacion_pnd_ods')) {
            return;
        }

        Schema::create('alineacion_pnd_ods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pnd_objetivo_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('ods_meta_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'pnd_objetivo_id',
                'ods_meta_id',
            ], 'alineacion_pnd_ods_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alineacion_pnd_ods');
    }
};
