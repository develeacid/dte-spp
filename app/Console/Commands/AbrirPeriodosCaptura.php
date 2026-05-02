<?php

namespace App\Console\Commands;

use App\Enums\EstadoAvance;
use App\Models\Mml\MetaPeriodo;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Notifications\PeriodoAbiertoNotification;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class AbrirPeriodosCaptura extends Command
{
    protected $signature = 'mir:abrir-periodos';

    protected $description = 'Abre periodos de captura cuya fecha de apertura ya se cumplio y crea avances EN_CAPTURA';

    public function handle(): int
    {
        $metasPendientes = MetaPeriodo::query()
            ->where('fecha_apertura', '<=', now()->toDateString())
            ->where('activo', true)
            ->whereDoesntHave('avance')
            ->with('indicador.mirNivel')
            ->get();

        $count = 0;

        foreach ($metasPendientes as $meta) {
            // Find the team for this indicator
            $teamId = $meta->indicador->mirNivel->team_id
                ?? $meta->indicador->mirNivel->programa?->team_id
                ?? null;

            // Find first user with capturar_avance permission in the team
            $capturadorId = null;
            $permissionExists = Permission::where('name', 'capturar_avance')
                ->where('guard_name', 'web')
                ->exists();

            $usuarios = collect();
            if ($teamId && $permissionExists) {
                $usuarios = User::permission('capturar_avance')
                    ->where(function ($q) use ($teamId) {
                        $q->whereHas('teams', fn ($sub) => $sub->where('teams.id', $teamId))
                            ->orWhereHas('ownedTeams', fn ($sub) => $sub->where('teams.id', $teamId));
                    })
                    ->get();

                $capturadorId = $usuarios->first()?->id;
            }

            $avance = Avance::create([
                'meta_periodo_id' => $meta->id,
                'indicador_id' => $meta->indicador_id,
                'estado' => EstadoAvance::EN_CAPTURA->value,
                'capturado_por' => $capturadorId,
            ]);

            // Notify all users with capturar_avance permission in the team
            if ($usuarios->isNotEmpty()) {
                $avance->load('indicador', 'metaPeriodo');

                foreach ($usuarios as $usuario) {
                    $usuario->notify(new PeriodoAbiertoNotification($avance));
                }
            }

            $count++;
        }

        $this->info("Periodos abiertos: {$count}");

        return self::SUCCESS;
    }
}
