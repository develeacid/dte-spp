<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importacion_reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')
                ->nullable()
                ->constrained('programa_presupuestarios')
                ->nullOnDelete();
            $table->foreignId('team_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('archivo_original');
            $table->string('formato', 10); // md, csv, xlsx
            $table->jsonb('datos_parseados');
            $table->jsonb('diagnostico')->nullable();
            $table->string('estado', 20)->default('pendiente'); // pendiente, procesado, descartado
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importacion_reportes');
    }
};
