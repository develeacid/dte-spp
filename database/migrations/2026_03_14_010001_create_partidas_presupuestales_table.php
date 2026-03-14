<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidas_presupuestales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')
                ->constrained('programa_presupuestarios')
                ->cascadeOnDelete();
            $table->string('clave_partida', 20);
            $table->string('descripcion', 255);
            $table->decimal('monto_aprobado', 15, 2);
            $table->decimal('monto_modificado', 15, 2)->nullable();
            $table->smallInteger('ejercicio_fiscal');
            $table->foreignId('team_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('registrado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['programa_presupuestario_id', 'ejercicio_fiscal']);
            $table->unique(['programa_presupuestario_id', 'clave_partida', 'ejercicio_fiscal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partidas_presupuestales');
    }
};
