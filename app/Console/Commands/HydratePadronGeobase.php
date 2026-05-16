<?php

namespace App\Console\Commands;

use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseException;
use App\Services\Padron\PadronProvisioningService;
use Illuminate\Console\Command;

class HydratePadronGeobase extends Command
{
    protected $signature = 'geobase:hydrate-padron {--dry-run : Solo listar programas que serian procesados, sin llamar a GeoBase}';

    protected $description = 'Re-registra en GeoBase todos los programas con padron_geobase_activo=true. Idempotente — pensado para correrse post-deploy o post-migrate:fresh cuando jobs async quedaron sin procesar.';

    public function handle(PadronProvisioningService $service): int
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
        $verbo = $dryRun ? 'serían procesados (dry-run)' : 'a procesar';

        $this->info("{$programas->count()} programas {$verbo}:");

        $rows = [];
        $errores = 0;

        foreach ($programas as $programa) {
            if ($dryRun) {
                $rows[] = [$programa->clave, $programa->nombre, 'dry-run'];

                continue;
            }

            try {
                $result = $service->register($programa);
                $rows[] = [
                    $programa->clave,
                    $programa->nombre,
                    sprintf('ok (%d componentes)', $result['componentes_registrados']),
                ];
            } catch (GeoBaseException $e) {
                $errores++;
                $rows[] = [
                    $programa->clave,
                    $programa->nombre,
                    'error: '.str($e->getMessage())->limit(80),
                ];
            }
        }

        $this->table(['Clave', 'Nombre', 'Resultado'], $rows);

        if ($errores > 0) {
            $this->error("{$errores} programa(s) fallaron. Revisa los mensajes arriba y vuelve a correr una vez resuelto.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
