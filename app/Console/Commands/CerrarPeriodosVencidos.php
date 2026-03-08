<?php

namespace App\Console\Commands;

use App\Enums\EstadoAvance;
use App\Models\Tracking\Avance;
use App\Notifications\AvanceVencidoNotification;
use Illuminate\Console\Command;

class CerrarPeriodosVencidos extends Command
{
    protected $signature = 'mir:cerrar-vencidos';
    protected $description = 'Marca como VENCIDO los avances cuyo periodo de captura ya cerro y no fueron aprobados';

    public function handle(): int
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

        foreach ($avancesVencidos as $avance) {
            $avance->update(['estado' => EstadoAvance::VENCIDO->value]);

            if ($avance->capturador) {
                $avance->capturador->notify(new AvanceVencidoNotification($avance));
            }

            $count++;
        }

        $this->info("Avances vencidos: {$count}");
        return self::SUCCESS;
    }
}
