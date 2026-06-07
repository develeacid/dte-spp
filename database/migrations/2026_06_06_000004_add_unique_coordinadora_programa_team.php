<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Dedup defensivo previo al índice único parcial: cada programa solo admite
        // un team con rol 'coordinadora' (la UR administradora del temario, C-036).
        // A diferencia de los niveles MIR, las filas duplicadas son relaciones reales
        // programa↔team — NO se borran. Solo se degrada el rol de los excedentes a
        // 'coadyuvante' (conservando el de menor id como coordinadora), porque el
        // conflicto es una disputa de rol, no de existencia de la relación.
        // Transacción explícita para garantizar atomicidad aunque una migración
        // futura ponga $withinTransaction = false.
        DB::transaction(function () {
            $this->dedupCoordinadoras();
        });

        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS programa_team_unica_coordinadora ON programa_team (programa_presupuestario_id) WHERE rol = 'coordinadora'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS programa_team_unica_coordinadora');
    }

    private function dedupCoordinadoras(): void
    {
        $grupos = DB::table('programa_team')
            ->select('programa_presupuestario_id')
            ->where('rol', 'coordinadora')
            ->groupBy('programa_presupuestario_id')
            ->havingRaw('count(*) > 1')
            ->pluck('programa_presupuestario_id');

        foreach ($grupos as $programaId) {
            $filas = DB::table('programa_team')
                ->where('rol', 'coordinadora')
                ->where('programa_presupuestario_id', $programaId)
                ->orderBy('id')
                ->pluck('id');

            $conservado = $filas->first();
            $degradados = $filas->slice(1)->values();

            DB::table('programa_team')
                ->whereIn('id', $degradados)
                ->update(['rol' => 'coadyuvante']);

            // Auditoría: solo corre cuando había duplicados (filtro count(*) > 1).
            Log::warning('Dedup programa_team: roles coordinadora duplicados degradados a coadyuvante', [
                'programa_presupuestario_id' => $programaId,
                'coordinadora_conservada_id' => $conservado,
                'degradados_a_coadyuvante_ids' => $degradados->all(),
            ]);
        }
    }
};
