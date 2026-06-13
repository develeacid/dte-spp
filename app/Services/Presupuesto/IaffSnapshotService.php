<?php

namespace App\Services\Presupuesto;

use App\Models\Presupuesto\Iaff;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use DomainException;

/**
 * D1 · Persiste el IAFF como snapshot inmutable por programa+ejercicio+trimestre.
 * El snapshot se (re)genera al exportar mientras NO esté firmado; firmarlo lo
 * congela (el hash sha256 garantiza la integridad).
 */
class IaffSnapshotService
{
    public function __construct(
        private readonly IaffConsolidacionService $consolidacion,
    ) {}

    public function generar(ProgramaPresupuestario $programa, int $ejercicio, int $trimestre, User $user): Iaff
    {
        $existente = Iaff::where('programa_id', $programa->id)
            ->where('ejercicio_fiscal', $ejercicio)
            ->where('trimestre', $trimestre)
            ->first();

        // Inmutable una vez firmado: re-generar no sobrescribe.
        if ($existente && $existente->estaFirmado()) {
            return $existente;
        }

        $payload = $this->armarPayload($programa, $ejercicio, $trimestre);
        $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return Iaff::updateOrCreate(
            [
                'programa_id' => $programa->id,
                'ejercicio_fiscal' => $ejercicio,
                'trimestre' => $trimestre,
            ],
            [
                'snapshot_payload' => $payload,
                'hash_sha256' => $hash,
                'generado_en' => now(),
                'generado_por' => $user->id,
            ],
        );
    }

    public function firmar(Iaff $iaff, User $user): Iaff
    {
        if ($iaff->estaFirmado()) {
            throw new DomainException('El IAFF ya está firmado y es inmutable.');
        }

        $iaff->update([
            'firmado_en' => now(),
            'firmado_por' => $user->id,
        ]);

        return $iaff->fresh();
    }

    private function armarPayload(ProgramaPresupuestario $programa, int $ejercicio, int $trimestre): array
    {
        $financiero = new IaffFinancialReportService($programa, $ejercicio, $trimestre);
        $rows = $financiero->rows();

        return [
            'programa' => ['id' => $programa->id, 'clave' => $programa->clave, 'nombre' => $programa->nombre],
            'ejercicio_fiscal' => $ejercicio,
            'trimestre' => $trimestre,
            'financiero' => [
                // Excluimos el modelo Eloquent embebido en cada fila; solo escalares.
                'partidas' => $rows->map(fn (array $r) => collect($r)->except('partida')->all())->values()->all(),
                'totales' => $financiero->totals($rows),
            ],
            'consolidacion' => $this->consolidacion->consolidar($programa, $ejercicio),
        ];
    }
}
