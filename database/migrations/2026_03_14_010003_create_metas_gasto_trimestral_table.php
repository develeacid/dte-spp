<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas_gasto_trimestral', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partida_presupuestal_id')
                ->constrained('partidas_presupuestales')
                ->cascadeOnDelete();
            $table->smallInteger('trimestre');
            $table->decimal('monto_programado', 15, 2);
            $table->text('justificacion')->nullable();
            $table->timestamps();

            $table->unique(['partida_presupuestal_id', 'trimestre']);
        });

        DB::statement('ALTER TABLE metas_gasto_trimestral ADD CONSTRAINT chk_meta_trimestre CHECK (trimestre BETWEEN 1 AND 4)');
        DB::statement('ALTER TABLE metas_gasto_trimestral ADD CONSTRAINT chk_meta_positiva CHECK (monto_programado >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('metas_gasto_trimestral');
    }
};
