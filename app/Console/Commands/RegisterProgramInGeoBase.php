<?php

namespace App\Console\Commands;

use App\Enums\TipoNivelMir;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RegisterProgramInGeoBase extends Command
{
    protected $signature = 'geobase:register-program {programa : Programa id or clave}';

    protected $description = 'Provision a programa (and its MIR componentes) in GeoBase using dte-spp ids as the source of truth.';

    public function handle(GeoBaseClient $client): int
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
            $programResponse = $client->registerProgram([
                'spp_program_id' => $programa->id,
                'clave' => $programa->clave,
                'name' => $programa->nombre,
                'ejercicio_fiscal' => $programa->ejercicio_fiscal,
                'activo' => true,
            ]);
            $this->line('  ✓ programa registrado/actualizado en GeoBase');
        } catch (GeoBaseException $e) {
            $this->error("Error registrando programa: {$e->getMessage()}");

            return self::FAILURE;
        }

        $componentes = $programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE)
            ->orderBy('orden')
            ->get();

        if ($componentes->isEmpty()) {
            $this->warn('  ! programa sin Componentes en MIR — provisioning detenido aquí');
        }

        $registered = 0;
        foreach ($componentes as $componente) {
            try {
                $client->registerComponent([
                    'spp_mir_nivel_id' => $componente->id,
                    'spp_program_id' => $programa->id,
                    'clave' => sprintf('%s-C%d', $programa->clave, $componente->orden),
                    'name' => str($componente->resumen_narrativo)->limit(255),
                    'description' => $componente->resumen_narrativo,
                    'activo' => true,
                ]);
                $this->line("  ✓ componente C{$componente->orden} (id={$componente->id})");
                $registered++;
            } catch (GeoBaseException $e) {
                $this->warn("  ! componente C{$componente->orden} fallo: {$e->getMessage()}");
            }
        }

        DB::transaction(function () use ($programa) {
            $programa->update(['padron_geobase_activo' => true]);
        });

        $this->info("Listo. {$registered} componentes provisionados. padron_geobase_activo=true");

        return self::SUCCESS;
    }
}
