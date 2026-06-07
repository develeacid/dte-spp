<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Limpieza defensiva de duplicados históricos antes de crear el índice
        // único parcial: la MIR solo admite un FIN y un PROPOSITO por programa.
        // Conserva el nivel de menor id; reasigna indicadores colgando de los
        // duplicados al nivel conservado para no perder datos ni romper FKs.
        // El dedup es destructivo (borra niveles y reasigna indicadores) sobre
        // datos reales de prod. Envolvemos explícitamente en una transacción para
        // garantizar atomicidad aunque una migración futura ponga
        // $withinTransaction = false (lo que rompería la transacción implícita).
        DB::transaction(function () {
            foreach (['fin', 'proposito'] as $tipo) {
                $this->dedupNiveles($tipo);
            }
        });

        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS mir_niveles_unico_fin ON mir_niveles (programa_presupuestario_id) WHERE tipo_nivel = 'fin'");
        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS mir_niveles_unico_proposito ON mir_niveles (programa_presupuestario_id) WHERE tipo_nivel = 'proposito'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS mir_niveles_unico_fin');
        DB::statement('DROP INDEX IF EXISTS mir_niveles_unico_proposito');
    }

    private function dedupNiveles(string $tipo): void
    {
        $grupos = DB::table('mir_niveles')
            ->select('programa_presupuestario_id')
            ->where('tipo_nivel', $tipo)
            ->groupBy('programa_presupuestario_id')
            ->havingRaw('count(*) > 1')
            ->pluck('programa_presupuestario_id');

        foreach ($grupos as $programaId) {
            $niveles = DB::table('mir_niveles')
                ->where('tipo_nivel', $tipo)
                ->where('programa_presupuestario_id', $programaId)
                ->orderBy('id')
                ->pluck('id');

            $conservado = $niveles->first();
            $duplicados = $niveles->slice(1)->values();

            // Reasignar indicadores de los duplicados al nivel conservado.
            $indicadoresReasignados = DB::table('indicadores')
                ->whereIn('mir_nivel_id', $duplicados)
                ->update(['mir_nivel_id' => $conservado]);

            DB::table('mir_niveles')->whereIn('id', $duplicados)->delete();

            // Auditoría: solo se ejecuta cuando había duplicados (el outer query
            // ya filtra por count(*) > 1), así que no genera ruido si no hay nada.
            Log::warning('Dedup mir_niveles: nivel duplicado eliminado', [
                'programa_presupuestario_id' => $programaId,
                'tipo_nivel' => $tipo,
                'nivel_conservado_id' => $conservado,
                'niveles_eliminados_ids' => $duplicados->all(),
                'indicadores_reasignados' => $indicadoresReasignados,
            ]);
        }
    }
};
