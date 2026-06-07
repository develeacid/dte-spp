<?php

use App\Models\Mml\MedioVerificacion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Normalización best-effort de medios_verificacion.frecuencia (string texto
     * libre) hacia los values del enum FrecuenciaMedicion. Idempotente: re-correr
     * es seguro porque los valores ya normalizados vuelven a coincidir consigo
     * mismos. Los valores no reconocidos se dejan intactos y se loggean para
     * revisión manual (no se borran ni se inventa un mapeo).
     */
    public function up(): void
    {
        $valores = DB::table('medios_verificacion')
            ->select('frecuencia', DB::raw('count(*) as total'))
            ->whereNotNull('frecuencia')
            ->where('frecuencia', '!=', '')
            ->groupBy('frecuencia')
            ->get();

        foreach ($valores as $fila) {
            $normalizado = MedioVerificacion::normalizarFrecuencia($fila->frecuencia);

            if ($normalizado === null) {
                Log::warning('MV frecuencia no normalizable', [
                    'valor' => $fila->frecuencia,
                    'count' => $fila->total,
                ]);

                continue;
            }

            if ($normalizado === $fila->frecuencia) {
                continue; // ya canónico, nada que hacer
            }

            DB::table('medios_verificacion')
                ->where('frecuencia', $fila->frecuencia)
                ->update(['frecuencia' => $normalizado]);
        }
    }

    /**
     * No-op intencional: la normalización es destructiva (perdemos la forma
     * original "Trimestral", " ANUAL ") y resulta inocua — el valor canónico es
     * un superset semántico válido. Revertir no aporta valor y reintroduciría
     * datos sucios, así que down() no hace nada.
     */
    public function down(): void
    {
        // no-op — ver docblock.
    }
};
