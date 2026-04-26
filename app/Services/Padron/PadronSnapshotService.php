<?php

namespace App\Services\Padron;

use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceEvidencia;
use App\Models\User;
use App\Services\GeoBase\GeoBaseClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PadronSnapshotService
{
    public function __construct(
        private GeoBaseClient $client,
    ) {}

    public function generar(
        ProgramaPresupuestario $programa,
        int $componenteId,
        User $user,
    ): AvanceEvidencia {
        $period = $this->trimestreActual();
        $cutoff = $this->fechaCorteActual();

        $response = $this->client->requestSnapshot([
            'program_id' => $programa->geobase_program_id,
            'component_id' => $componenteId,
            'period' => $period,
            'cutoff_date' => $cutoff,
        ]);

        $snapshot = $response['data'] ?? [];
        $snapshotId = (int) ($snapshot['id'] ?? 0);
        $hash = (string) ($snapshot['snapshot_hash'] ?? '');
        $rowCount = (int) ($snapshot['row_count'] ?? 0);
        $cutoffDate = Carbon::parse($snapshot['cutoff_date'] ?? $cutoff)->toDateString();

        $avance = $this->findAvanceParaComponente($componenteId, $period);

        return DB::transaction(function () use ($snapshotId, $hash, $rowCount, $cutoffDate, $period, $user, $avance) {
            $evidencia = AvanceEvidencia::create([
                'avance_id' => $avance?->id,
                'nombre_archivo' => "snapshot-{$snapshotId}-{$cutoffDate}.csv",
                'ruta_archivo' => '',
                'mime_type' => 'text/csv',
                'tamano_bytes' => 0,
                'hash_archivo' => $hash,
                'geobase_snapshot_id' => $snapshotId,
                'nombre_documento' => "Snapshot Padrón Componente",
                'area_generadora' => 'GeoBase (manual)',
                'fecha_documento' => $cutoffDate,
                'subido_por' => $user->id,
            ]);

            activity('padron-snapshot')
                ->causedBy($user)
                ->performedOn($evidencia)
                ->withProperties([
                    'snapshot_id' => $snapshotId,
                    'period' => $period,
                    'row_count' => $rowCount,
                    'avance_id' => $avance?->id,
                ])
                ->log('Snapshot manual generado');

            return $evidencia;
        });
    }

    /**
     * Find the Avance that this snapshot should attach to: same Componente
     * (MirNivel id) + same trimestre + same ejercicio fiscal. Returns null
     * when no matching Avance exists, in which case the evidence is created
     * unbound (avance_id=null) and acts as standalone audit material.
     */
    private function findAvanceParaComponente(int $componenteId, string $period): ?Avance
    {
        if (! preg_match('/^(\d{4})-Q([1-4])$/', $period, $m)) {
            return null;
        }
        [$ejercicio, $trimestre] = [(int) $m[1], (int) $m[2]];

        return Avance::query()
            ->whereHas('indicador', fn ($q) => $q->where('mir_nivel_id', $componenteId))
            ->whereHas('metaPeriodo', fn ($q) => $q
                ->where('periodo', $trimestre)
                ->where('ejercicio_fiscal', $ejercicio))
            ->orderBy('id')
            ->first();
    }

    private function trimestreActual(): string
    {
        $mes = now()->month;
        $trim = (int) ceil($mes / 3);

        return now()->year . "-Q{$trim}";
    }

    private function fechaCorteActual(): string
    {
        return now()->endOfQuarter()->toDateString();
    }
}
