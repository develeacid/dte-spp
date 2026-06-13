<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_fiscales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_id')->constrained('programa_presupuestarios')->cascadeOnDelete();
            $table->integer('ejercicio_fiscal');
            $table->string('estado', 20)->default('prevalidacion');
            // Audit del avance de fases: [{estado, en, por}] append-only.
            $table->jsonb('historial')->default('[]');
            $table->timestamps();

            $table->unique(['programa_id', 'ejercicio_fiscal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_fiscales');
    }
};
