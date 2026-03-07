<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ped_planes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('nivel_gobierno')->default('estatal'); // estatal, municipal
            $table->unsignedSmallInteger('periodo_inicio'); // Ej: 2025
            $table->unsignedSmallInteger('periodo_fin');    // Ej: 2030
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });

        // Constraint parcial: solo un plan activo a la vez
        // PostgreSQL nativo - Laravel Blueprint no lo soporta
        DB::statement(
            'CREATE UNIQUE INDEX ped_planes_activo_unique ON ped_planes (activo) WHERE activo = true'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ped_planes');
    }
};
