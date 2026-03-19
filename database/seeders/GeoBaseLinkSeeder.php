<?php

namespace Database\Seeders;

use App\Models\Mml\IndicadorVariable;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\AvanceVariable;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Database\Seeder;

class GeoBaseLinkSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Intentando conectar con GeoBase...');

        try {
            $client = app(GeoBaseClient::class);
        } catch (\Exception $e) {
            $this->command->error("No se pudo instanciar GeoBaseClient: {$e->getMessage()}");
            $this->command->warn('  → Verifica que GEOBASE_API_URL y GEOBASE_API_TOKEN estén configurados en .env');
            $this->command->warn('  → Ejecuta primero: cd /home/eleacid/code/laravel/geobase && ./vendor/bin/sail artisan migrate:fresh --seed');

            return;
        }

        $programas = ProgramaPresupuestario::whereNotNull('geobase_program_id')->get();

        if ($programas->isEmpty()) {
            $this->command->warn('No hay programas con geobase_program_id. Ejecuta Fase1PlaneacionMmlSeeder primero.');

            return;
        }

        $linked = 0;
        $failed = 0;

        foreach ($programas as $programa) {
            try {
                $coverage = $client->getProgramCoverage($programa->geobase_program_id);
                $count = $coverage['total_beneficiaries'] ?? $coverage['count'] ?? 0;
                $this->command->info("  {$programa->clave} -> GeoBase program #{$programa->geobase_program_id}: {$count} beneficiarios");
                $linked++;
            } catch (GeoBaseException $e) {
                $this->command->warn("  {$programa->clave} -> GeoBase error: {$e->getMessage()}");
                $failed++;
            } catch (\Exception $e) {
                $this->command->warn("  {$programa->clave} -> Conexion fallida: {$e->getMessage()}");
                $failed++;
            }
        }

        $variables = IndicadorVariable::whereNotNull('geobase_endpoint_type')->get();
        $synced = 0;

        foreach ($variables as $variable) {
            $avanceVars = AvanceVariable::where('indicador_variable_id', $variable->id)
                ->whereHas('avance', fn ($q) => $q->where('estado', 'en_captura'))
                ->get();

            foreach ($avanceVars as $av) {
                try {
                    $response = $client->getProgramCoverage($variable->geobase_reference_id);
                    $value = $response[$variable->geobase_value_key ?? 'count'] ?? null;
                    if ($value !== null) {
                        $av->update(['valor' => $value, 'synced_from_geobase' => true, 'synced_at' => now()]);
                        $synced++;
                    }
                } catch (\Exception $e) {
                    // Silent — GeoBase may not have data for all references
                }
            }
        }

        $this->command->newLine();
        $this->command->info("Resultado: Programas vinculados: {$linked}, fallidos: {$failed}, variables sincronizadas: {$synced}");

        if ($failed > 0) {
            $this->command->warn('Para resolver fallos:');
            $this->command->warn('  1. Verifica que GeoBase este corriendo en puerto 8081');
            $this->command->warn('  2. Verifica GEOBASE_API_TOKEN en .env');
            $this->command->warn('  3. Re-ejecuta: ./vendor/bin/sail artisan db:seed --class=GeoBaseLinkSeeder');
        }
    }
}
