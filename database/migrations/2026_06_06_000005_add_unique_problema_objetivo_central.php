<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // C-016 (V2-B11): un árbol del problema admite UN solo nodo
        // "problema central"; análogamente el árbol de objetivos admite UN solo
        // "objetivo central". A nivel aplicación esto ya se respeta
        // (DefinicionProblema usa updateOrCreate keyed en tipo_nodo, y los
        // lectores usan ->first()), pero NADA a nivel BD impide que un insert
        // directo (factory, tinker, builder IA con bug) cree un segundo nodo
        // raíz que quedaría enmascarado por el ->first(). Blindamos con índice
        // único parcial, mismo patrón que mir_niveles_unico_fin/proposito.
        //
        // Limpieza defensiva de duplicados históricos antes de crear el índice:
        // conserva el nodo de menor id; recuelga los hijos de los duplicados al
        // nodo conservado para no perder ramas del árbol, luego elimina los
        // duplicados. Envuelto en transacción explícita por atomicidad.
        DB::transaction(function () {
            foreach (['problema_central', 'objetivo_central'] as $tipo) {
                $this->dedupNodosRaiz($tipo);
            }
        });

        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS arbol_nodos_unico_problema_central ON arbol_nodos (arbol_id) WHERE tipo_nodo = 'problema_central'");
        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS arbol_nodos_unico_objetivo_central ON arbol_nodos (arbol_id) WHERE tipo_nodo = 'objetivo_central'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS arbol_nodos_unico_problema_central');
        DB::statement('DROP INDEX IF EXISTS arbol_nodos_unico_objetivo_central');
    }

    private function dedupNodosRaiz(string $tipo): void
    {
        $grupos = DB::table('arbol_nodos')
            ->select('arbol_id')
            ->where('tipo_nodo', $tipo)
            ->groupBy('arbol_id')
            ->havingRaw('count(*) > 1')
            ->pluck('arbol_id');

        foreach ($grupos as $arbolId) {
            $nodos = DB::table('arbol_nodos')
                ->where('tipo_nodo', $tipo)
                ->where('arbol_id', $arbolId)
                ->orderBy('id')
                ->pluck('id');

            $conservado = $nodos->first();
            $duplicados = $nodos->slice(1)->values();

            // Recolgar hijos de los nodos raíz duplicados al nodo conservado
            // para no perder ramas del árbol.
            $hijosRecolgados = DB::table('arbol_nodos')
                ->whereIn('parent_id', $duplicados)
                ->update(['parent_id' => $conservado]);

            DB::table('arbol_nodos')->whereIn('id', $duplicados)->delete();

            Log::warning('Dedup arbol_nodos: nodo raíz duplicado eliminado', [
                'arbol_id' => $arbolId,
                'tipo_nodo' => $tipo,
                'nodo_conservado_id' => $conservado,
                'nodos_eliminados_ids' => $duplicados->all(),
                'hijos_recolgados' => $hijosRecolgados,
            ]);
        }
    }
};
