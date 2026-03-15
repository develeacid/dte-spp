<?php

namespace App\Console\Commands;

use App\Enums\EstadoAvance;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Services\Tracking\AvanceEstadoService;
use Illuminate\Console\Command;

class CerrarPeriodosVencidos extends Command
{
    protected $signature = 'mir:cerrar-vencidos';
    protected $description = 'Marca como VENCIDO los avances cuyo periodo de captura ya cerro y no fueron aprobados';

    public function handle(AvanceEstadoService $service): int
    {
        $avancesVencidos = Avance::query()
            ->whereHas('metaPeriodo', fn ($q) => $q->where('fecha_cierre', '<', now()->toDateString()))
            ->whereNotIn('estado', [
                EstadoAvance::APROBADO->value,
                EstadoAvance::VENCIDO->value,
            ])
            ->with(['indicador', 'metaPeriodo', 'capturador'])
            ->get();

        $count = 0;
        $sistemaUser = null;

        foreach ($avancesVencidos as $avance) {
            $usuario = $avance->capturador;

            if (! $usuario) {
                $sistemaUser ??= User::first();
                $usuario = $sistemaUser;
            }

            $service->transicionar($avance, EstadoAvance::VENCIDO, $usuario);
            $count++;
        }

        $this->info("Avances vencidos: {$count}");
        return self::SUCCESS;
    }
}
