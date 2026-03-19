<?php

namespace App\Console\Commands;

use App\Models\Mml\IndicadorVariable;
use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceVariable;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGeoBaseAvances extends Command
{
    protected $signature = 'geobase:sync-avances
                            {--dry-run : Show what would be synced without executing}
                            {--program= : Sync only a specific programa_presupuestario ID}';

    protected $description = 'Sync all GeoBase-linked indicator variables for open avances';

    public function handle(GeoBaseClient $client): int
    {
        $dryRun = $this->option('dry-run');
        $programId = $this->option('program');

        $this->info($dryRun ? '[DRY RUN] Scanning...' : 'Syncing GeoBase variables...');

        // Find avances in open periods (en_captura state)
        $query = Avance::where('estado', 'en_captura')
            ->whereNull('congelado_at')
            ->whereHas('indicador.variables', fn ($q) => $q->whereNotNull('geobase_endpoint_type'));

        if ($programId) {
            $query->whereHas('indicador.mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $programId));
        }

        $avances = $query->with(['indicador.variables', 'variables'])->get();

        $synced = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($avances as $avance) {
            $geobaseVars = $avance->indicador->variables->filter(fn ($v) => $v->hasGeoBaseLink());

            foreach ($geobaseVars as $variable) {
                if ($dryRun) {
                    $this->line("  Would sync: {$variable->nombre} (avance #{$avance->id})");
                    $skipped++;
                    continue;
                }

                try {
                    $response = match ($variable->geobase_endpoint_type) {
                        'component_coverage' => $client->getProgramCoverage($variable->geobase_reference_id),
                        'program_coverage' => $client->getProgramCoverage($variable->geobase_reference_id),
                        default => null,
                    };

                    if ($response === null) {
                        $skipped++;
                        continue;
                    }

                    $valueKey = $variable->geobase_value_key ?? 'count';
                    $value = $response[$valueKey] ?? null;

                    if ($value === null) {
                        $skipped++;
                        continue;
                    }

                    AvanceVariable::updateOrCreate(
                        [
                            'avance_id' => $avance->id,
                            'indicador_variable_id' => $variable->id,
                        ],
                        ['valor' => $value],
                    );

                    $synced++;
                } catch (GeoBaseException $e) {
                    $this->warn("  Failed: {$variable->nombre} — {$e->getMessage()}");
                    Log::warning("geobase:sync-avances failed for variable {$variable->id}", [
                        'error' => $e->getMessage(),
                        'avance_id' => $avance->id,
                    ]);
                    $failed++;
                }
            }
        }

        $this->info("Done. Synced: {$synced}, Failed: {$failed}, Skipped: {$skipped}");

        Log::info('geobase:sync-avances completed', compact('synced', 'failed', 'skipped'));

        return self::SUCCESS;
    }
}
