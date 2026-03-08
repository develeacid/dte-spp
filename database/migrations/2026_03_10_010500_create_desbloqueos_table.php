<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desbloqueos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avance_id')
                ->constrained('avances')->cascadeOnDelete();
            $table->text('motivo');
            $table->foreignId('solicitado_por')
                ->constrained('users')->cascadeOnDelete();
            $table->foreignId('resuelto_por')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado', 20)->default('pendiente');
            $table->text('resolucion')->nullable();
            $table->timestamp('resuelto_at')->nullable();
            $table->timestamps();

            $table->index(['avance_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desbloqueos');
    }
};
