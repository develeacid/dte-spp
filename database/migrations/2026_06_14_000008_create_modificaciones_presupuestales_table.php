<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adecuaciones presupuestarias (V2-E5): log de ampliaciones/reducciones por partida.
     * El monto_modificado de la partida se deriva de este log (mantenido por el servicio).
     */
    public function up(): void
    {
        Schema::create('modificaciones_presupuestales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partida_presupuestal_id')->constrained('partidas_presupuestales')->cascadeOnDelete();
            $table->string('tipo', 12); // ampliacion | reduccion
            $table->decimal('monto', 15, 2);
            $table->date('fecha');
            $table->string('oficio')->nullable();       // folio del oficio de adecuación
            $table->text('justificacion')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['partida_presupuestal_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modificaciones_presupuestales');
    }
};
