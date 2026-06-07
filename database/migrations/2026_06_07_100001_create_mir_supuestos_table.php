<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supuestos estructurados por nivel MIR (V2-A8, C-035) con los 3 atributos
     * de validez del temario: externo + relevante + razonablemente probable
     * (C-075). Backfill idempotente: el texto legacy de mir_niveles.supuestos
     * se convierte en 1 supuesto sin validez marcada. La columna legacy queda
     * deprecada (sin lectores ni escritores; drop en sprint futuro).
     */
    public function up(): void
    {
        if (! Schema::hasTable('mir_supuestos')) {
            Schema::create('mir_supuestos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mir_nivel_id')
                    ->constrained('mir_niveles')->cascadeOnDelete();
                $table->text('descripcion');
                $table->boolean('es_externo')->default(false);
                $table->boolean('es_relevante')->default(false);
                $table->boolean('probabilidad_razonable')->default(false);
                $table->integer('orden')->default(1);
                $table->timestamps();

                $table->index('mir_nivel_id');
            });
        }

        // Backfill idempotente: solo niveles con texto legacy y sin supuestos
        // estructurados previos (capturados a mano o por corridas anteriores).
        DB::table('mir_niveles')
            ->whereNotNull('supuestos')
            ->where('supuestos', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($niveles) {
                foreach ($niveles as $nivel) {
                    $existe = DB::table('mir_supuestos')
                        ->where('mir_nivel_id', $nivel->id)
                        ->exists();

                    if (! $existe) {
                        DB::table('mir_supuestos')->insert([
                            'mir_nivel_id' => $nivel->id,
                            'descripcion' => $nivel->supuestos,
                            'es_externo' => false,
                            'es_relevante' => false,
                            'probabilidad_razonable' => false,
                            'orden' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('mir_supuestos');
    }
};
