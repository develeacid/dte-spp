<?php

namespace App\Listeners\GeoBase;

use App\Events\GeoBase\SnapshotGenerated;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceEvidencia;
use Illuminate\Support\Facades\Log;

class StoreSnapshotHash
{
    public function handle(SnapshotGenerated $event): void
    {
        // sppProgramId is the dte-spp programa.id (geobase echoes it back).
        $programa = ProgramaPresupuestario::find($event->sppProgramId);

        if (! $programa) {
            Log::warning('StoreSnapshotHash: no programa found for spp_program_id', [
                'spp_program_id' => $event->sppProgramId,
            ]);

            return;
        }

        // GeoBase emits period as "YYYY-QN"; metas_periodo.periodo is smallint
        // (1..4) and ejercicio_fiscal is the year. Parse to match.
        if (! preg_match('/^(\d{4})-Q([1-4])$/', $event->period, $m)) {
            Log::warning('StoreSnapshotHash: invalid period format', [
                'period' => $event->period,
            ]);

            return;
        }
        [$ejercicio, $trimestre] = [(int) $m[1], (int) $m[2]];

        $avance = Avance::whereHas('indicador.mirNivel', function ($query) use ($programa) {
            $query->where('programa_presupuestario_id', $programa->id);
        })
            ->whereHas('metaPeriodo', function ($query) use ($trimestre, $ejercicio) {
                $query->where('periodo', $trimestre)->where('ejercicio_fiscal', $ejercicio);
            })
            ->first();

        if (! $avance) {
            Log::info('StoreSnapshotHash: no avance found for programa and period', [
                'programa_id' => $programa->id,
                'period' => $event->period,
            ]);

            return;
        }

        AvanceEvidencia::firstOrCreate(
            [
                'avance_id' => $avance->id,
                'geobase_snapshot_id' => $event->snapshotId,
            ],
            [
                'nombre_archivo' => "snapshot-{$event->snapshotId}-{$event->period}.csv",
                'ruta_archivo' => '',
                'mime_type' => 'text/csv',
                'tamano_bytes' => 0,
                'hash_archivo' => $event->snapshotHash,
                'nombre_documento' => "Snapshot GeoBase {$event->period}",
                'area_generadora' => 'GeoBase (automatico)',
                'fecha_documento' => now(),
                'subido_por' => $avance->capturado_por,
            ]
        );
    }
}
