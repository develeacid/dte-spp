<?php

namespace App\Console\Commands;

use App\Enums\TipoNivelMir;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use Illuminate\Console\Command;

class HydrateIndicadorVariablesGeobase extends Command
{
    protected $signature = 'geobase:hydrate-indicador-variables {--dry-run : Solo listar variables que serían vinculadas, sin escribir}';

    protected $description = 'Vincula la primera variable (orden=1) de cada indicador asociado a niveles COMPONENTE (y PROPOSITO para ISM-001) de programas con padron_geobase_activo=true. Idempotente — replica la lógica de Fase1PlaneacionMmlSeeder para BDs existentes sin migrate:fresh.';

    public function handle(): int
    {
        $programas = ProgramaPresupuestario::query()
            ->where('padron_geobase_activo', true)
            ->orderBy('id')
            ->get();

        if ($programas->isEmpty()) {
            $this->info('No hay programas con padron_geobase_activo=true. Nada que hacer.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $linked = 0;
        $skipped = 0;
        $rows = [];

        foreach ($programas as $programa) {
            $tiposRelevantes = [TipoNivelMir::COMPONENTE];
            if ($programa->clave === 'ISM-001') {
                $tiposRelevantes[] = TipoNivelMir::PROPOSITO;
            }

            $niveles = MirNivel::query()
                ->where('programa_presupuestario_id', $programa->id)
                ->whereIn('tipo_nivel', $tiposRelevantes)
                ->with('indicadores')
                ->get();

            foreach ($niveles as $nivel) {
                $isComponente = $nivel->tipo_nivel === TipoNivelMir::COMPONENTE;
                $endpoint = $isComponente ? 'component_coverage' : 'program_coverage';
                $referenceId = $isComponente ? $nivel->id : $programa->id;

                foreach ($nivel->indicadores as $indicador) {
                    $firstVar = IndicadorVariable::where('indicador_id', $indicador->id)
                        ->where('orden', 1)
                        ->first();

                    if (! $firstVar) {
                        continue;
                    }

                    if ($firstVar->geobase_endpoint_type !== null) {
                        $skipped++;
                        $rows[] = [$programa->clave, $nivel->tipo_nivel->value, $indicador->nombre, $firstVar->simbolo, 'ya vinculada'];

                        continue;
                    }

                    if (! $dryRun) {
                        $firstVar->update([
                            'geobase_endpoint_type' => $endpoint,
                            'spp_reference_id' => $referenceId,
                            'geobase_value_key' => 'count',
                        ]);
                    }

                    $linked++;
                    $rows[] = [
                        $programa->clave,
                        $nivel->tipo_nivel->value,
                        $indicador->nombre,
                        $firstVar->simbolo,
                        $dryRun ? "dry-run → {$endpoint}" : "vinculada → {$endpoint}",
                    ];
                }
            }
        }

        $this->table(['Programa', 'Nivel', 'Indicador', 'Variable', 'Resultado'], $rows);
        $this->info(sprintf('Total: %d vinculadas, %d ya estaban (%s)', $linked, $skipped, $dryRun ? 'dry-run' : 'commit'));

        return self::SUCCESS;
    }
}
