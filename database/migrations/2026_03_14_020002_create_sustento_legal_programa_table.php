<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sustento_legal_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')
                ->constrained('programa_presupuestarios')
                ->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->foreignId('catalogo_ordenamiento_id')
                ->nullable()
                ->constrained('catalogo_ordenamientos')
                ->nullOnDelete();
            $table->string('ordenamiento', 255);
            $table->string('articulo', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('nivel_jerarquia', 30);
            $table->boolean('vigente')->default(true);
            $table->foreignId('registrado_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('validado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('validado_at')->nullable();
            $table->foreignId('team_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->index(['programa_presupuestario_id', 'tipo']);
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sustento_legal_programa');
    }
};
