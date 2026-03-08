<?php

namespace App\Services\Tracking;

use App\Enums\EstadoAvance;
use App\Exceptions\TransicionInvalidaException;
use App\Models\Tracking\Avance;
use App\Models\User;

class AvanceEstadoService
{
    private const TRANSICIONES = [
        'en_captura' => ['en_revision', 'vencido'],
        'en_revision' => ['observado', 'aprobado', 'vencido'],
        'observado' => ['en_captura'],
        'aprobado' => [],
        'vencido' => [],
    ];

    public function transicionar(Avance $avance, EstadoAvance $nuevoEstado, User $usuario, ?string $observacion = null): void
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
    }
}
