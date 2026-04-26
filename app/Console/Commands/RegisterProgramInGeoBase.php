<?php

namespace App\Console\Commands;

use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseException;
use App\Services\Padron\PadronProvisioningService;
use Illuminate\Console\Command;

class RegisterProgramInGeoBase extends Command
{
    protected $signature = 'geobase:register-program {programa : Programa id or clave}';

    protected $description = 'Provision a programa (and its MIR componentes) in GeoBase using dte-spp ids as the source of truth.';

    public function handle(PadronProvisioningService $service): int
    {
        $arg = $this->argument('programa');
        $programa = is_numeric($arg)
            ? ProgramaPresupuestario::find((int) $arg)
            : ProgramaPresupuestario::where('clave', $arg)->first();

        if (! $programa) {
            $this->error("Programa no encontrado: {$arg}");

            return self::FAILURE;
        }

        $this->info("Programa: {$programa->clave} — {$programa->nombre} (id={$programa->id})");

        try {
            $result = $service->register($programa);
        } catch (GeoBaseException $e) {
            $this->error("Error registrando programa: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Listo. {$result['componentes_registrados']} componentes provisionados. padron_geobase_activo=true");

        return self::SUCCESS;
    }
}
