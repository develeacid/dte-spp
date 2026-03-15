<?php

namespace Database\Seeders;

use App\Models\ProgramaPresupuestario;
use App\Services\EstadoConsolidadoService;
use Illuminate\Database\Seeder;

class Fase5ConsolidadoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('No se puede ejecutar en producción.');
            return;
        }

        $this->command->info('Fase 5: Calculando estado consolidado tripartita...');

        $service = app(EstadoConsolidadoService::class);
        $ejercicio = 2025;

        $programas = ProgramaPresupuestario::all();
        $stats = ['completo' => 0, 'parcial' => 0, 'critico' => 0];

        foreach ($programas as $programa) {
            $estado = $service->recalcular($programa->id, $ejercicio);
            $stats[$estado->consolidado]++;

            $this->command->line("  → {$programa->clave}: {$estado->consolidado} ({$estado->validaciones_completas}/3)");
        }

        $this->command->table(
            ['Estado', 'Cantidad'],
            collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->toArray()
        );

        $this->command->info('Fase 5 completada.');
    }
}
