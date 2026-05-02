<?php

namespace App\Console\Commands;

use App\Enums\TipoNivelMir;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\GeoBase\GeoBaseException;
use App\Services\Padron\PadronSnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateTrimestralSnapshots extends Command
{
    protected $signature = 'geobase:snapshot-trimestral
                            {--dry-run : Show what would be generated without executing}
                            {--program= : Run only for a specific programa_presupuestario id}';

    protected $description = 'Generate Padron snapshots for the current trimestre across all programas with padron_geobase_activo=true (idempotent).';

    public function handle(PadronSnapshotService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $programId = $this->option('program');

        $query = ProgramaPresupuestario::where('padron_geobase_activo', true)
            ->with(['team', 'mirNiveles']);

        if ($programId) {
            $query->where('id', (int) $programId);
        }

        $programas = $query->get();
        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($programas as $programa) {
            $owner = $this->resolveActor($programa);
            if (! $owner) {
                $this->warn("[{$programa->clave}] sin team owner — omitido");
                $skipped++;

                continue;
            }

            $componentes = $programa->mirNiveles
                ->where('tipo_nivel', TipoNivelMir::COMPONENTE);

            if ($componentes->isEmpty()) {
                $this->warn("[{$programa->clave}] sin Componentes — omitido");
                $skipped++;

                continue;
            }

            foreach ($componentes as $componente) {
                if ($dryRun) {
                    $this->line("[DRY] {$programa->clave} → C{$componente->orden}");

                    continue;
                }

                try {
                    $service->generar($programa, $componente->id, $owner);
                    $generated++;
                } catch (GeoBaseException $e) {
                    $this->error("[{$programa->clave}] C{$componente->orden}: {$e->getMessage()}");
                    Log::warning('GenerateTrimestralSnapshots: failed', [
                        'programa_id' => $programa->id,
                        'componente_id' => $componente->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }
        }

        $this->info($dryRun
            ? "[DRY RUN] {$programas->count()} programas evaluados."
            : "Generados: {$generated} · Omitidos: {$skipped} · Fallos: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveActor(ProgramaPresupuestario $programa): ?User
    {
        $userId = $programa->team?->user_id;

        return $userId ? User::find($userId) : null;
    }
}
