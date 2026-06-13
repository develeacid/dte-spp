<?php

namespace App\Console\Commands;

use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAtendidaProposito extends Command
{
    protected $signature = 'geobase:sync-atendida
                            {--dry-run : Mostrar qué se actualizaría sin escribir}
                            {--program= : Correr solo para un programa_presupuestario id}
                            {--ejercicio= : Ejercicio a sincronizar (default: año actual)}';

    protected $description = 'Sincroniza la Población Atendida del Propósito desde geobase hacia poblaciones_programa (idempotente, update-only).';

    public function handle(GeoBaseClient $client): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $ejercicio = (int) ($this->option('ejercicio') ?: now()->year);

        $query = ProgramaPresupuestario::where('padron_geobase_activo', true);
        if ($programId = $this->option('program')) {
            $query->where('id', (int) $programId);
        }

        $synced = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($query->get() as $programa) {
            $poblacion = $programa->poblacion()->where('anio_ejercicio', $ejercicio)->first();

            if (! $poblacion) {
                $this->warn("[{$programa->clave}] sin embudo de población para {$ejercicio} — omitido");
                $skipped++;

                continue;
            }

            try {
                $resp = $client->getAtendidaProposito($programa->id, $ejercicio);
                $atendida = (int) ($resp['poblacion_atendida'] ?? 0);

                if ($dryRun) {
                    $this->line("[DRY] {$programa->clave}: atendida {$ejercicio} = {$atendida}");
                    $synced++;

                    continue;
                }

                $poblacion->update([
                    'atendida_cantidad' => $atendida,
                    'atendida_sync_at' => now(),
                ]);
                $this->info("[{$programa->clave}] atendida {$ejercicio} = {$atendida}");
                $synced++;
            } catch (GeoBaseException $e) {
                $this->error("[{$programa->clave}]: {$e->getMessage()}");
                Log::warning('geobase:sync-atendida failed', [
                    'programa_id' => $programa->id,
                    'ejercicio' => $ejercicio,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->line("Sincronizados: {$synced} · Omitidos: {$skipped} · Fallidos: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
