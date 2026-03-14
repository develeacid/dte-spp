<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validacion_juridica_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')
                ->constrained('programa_presupuestarios')
                ->cascadeOnDelete();
            $table->smallInteger('ejercicio_fiscal');
            $table->string('estado', 20)->default('pendiente');
            $table->boolean('tiene_facultad_ur')->default(false);
            $table->boolean('tiene_mandato_gasto')->default(false);
            $table->boolean('tiene_rop')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('validado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('validado_at')->nullable();
            $table->timestamps();

            $table->unique(['programa_presupuestario_id', 'ejercicio_fiscal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validacion_juridica_programa');
    }
};
