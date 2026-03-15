<?php

namespace App\Services\Tracking;

use App\Enums\EstadoAvance;
use App\Exceptions\TransicionInvalidaException;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Notifications\AvanceEnRevisionNotification;
use App\Notifications\AvanceObservadoNotification;
use App\Notifications\AvanceVencidoNotification;

class AvanceEstadoService
{
    private const TRANSICIONES = [
        'en_captura' => ['en_revision', 'vencido'],
        'en_revision' => ['observado', 'aprobado', 'vencido'],
        'observado' => ['en_captura'],
        'aprobado' => [],
        'vencido' => [],
    ];

    public function transicionar(Avance $avance, EstadoAvance $nuevoEstado, User $usuario, ?string $observacion = null, bool $notificar = true): void
    {
        $estadoActual = $avance->estado->value;
        $permitidos = self::TRANSICIONES[$estadoActual] ?? [];

        if (! in_array($nuevoEstado->value, $permitidos)) {
            throw new TransicionInvalidaException(
                "Transición inválida: {$estadoActual} → {$nuevoEstado->value}"
            );
        }

        $estadoAnterior = $avance->estado;
        $avance->estado = $nuevoEstado;

        if ($nuevoEstado === EstadoAvance::APROBADO) {
            $avance->congelado_at = now();
        }

        // Append to historial (append-only JSONB)
        $historial = $avance->historial_observaciones ?? [];
        $historial[] = [
            'fecha' => now()->toISOString(),
            'usuario_id' => $usuario->id,
            'usuario_nombre' => $usuario->name,
            'accion' => $nuevoEstado->value,
            'estado_anterior' => $estadoAnterior->value,
            'estado_nuevo' => $nuevoEstado->value,
            'observacion' => $observacion,
        ];
        $avance->historial_observaciones = $historial;
        $avance->save();

        if ($notificar) {
            $this->despacharNotificacion($avance, $nuevoEstado, $usuario, $observacion);
        }
    }

    private function despacharNotificacion(Avance $avance, EstadoAvance $nuevoEstado, User $usuario, ?string $observacion): void
    {
        $avance->loadMissing(['indicador.mirNivel.programa', 'capturador', 'metaPeriodo']);

        match ($nuevoEstado) {
            EstadoAvance::EN_REVISION => $this->notificarPlaneadores(
                $this->resolverTeamId($avance),
                new AvanceEnRevisionNotification($avance, $usuario->name),
            ),
            EstadoAvance::OBSERVADO => $avance->capturador?->notify(
                new AvanceObservadoNotification($avance, $observacion ?? ''),
            ),
            EstadoAvance::VENCIDO => $avance->capturador?->notify(
                new AvanceVencidoNotification($avance),
            ),
            default => null,
        };
    }

    private function resolverTeamId(Avance $avance): ?int
    {
        return $avance->indicador->mirNivel->team_id
            ?? $avance->indicador->mirNivel->programa?->team_id;
    }

    private function notificarPlaneadores(?int $teamId, $notification): void
    {
        if (! $teamId) {
            return;
        }

        $planeadores = User::permission('revisar_avance')
            ->whereHas('teams', fn ($q) => $q->where('teams.id', $teamId))
            ->get();

        foreach ($planeadores as $planeador) {
            $planeador->notify($notification);
        }
    }
}
