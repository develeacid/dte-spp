<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_normativos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')
                ->constrained('programa_presupuestarios')
                ->cascadeOnDelete();
            $table->string('tipo_documento', 30);
            $table->string('nombre', 255);
            $table->string('archivo_path', 500);
            $table->string('archivo_hash', 64)->nullable();
            $table->integer('archivo_size')->nullable();
            $table->date('fecha_publicacion')->nullable();
            $table->date('fecha_vigencia')->nullable();
            $table->boolean('verificado')->default(false);
            $table->foreignId('verificado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('verificado_at')->nullable();
            $table->foreignId('registrado_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('team_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->index(['programa_presupuestario_id', 'tipo_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_normativos');
    }
};
