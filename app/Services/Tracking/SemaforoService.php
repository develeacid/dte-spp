<?php

namespace App\Services\Tracking;

use App\Enums\SentidoIndicador;
use App\Models\Mml\Indicador;

class SemaforoService
{
    /**
     * Calculate traffic light color based on resultado vs indicator ranges.
     *
     * @return string verde|amarillo|rojo|rojo_alto
     */
    public function calcular(float $resultado, Indicador $indicador, ?float $metaPeriodo = null): string
    {
        $sentido = $indicador->sentido ?? SentidoIndicador::ASCENDENTE;

        $hasRanges = $indicador->rango_verde_min !== null
            || $indicador->rango_verde_max !== null
            || $indicador->rango_amarillo_min !== null
            || $indicador->rango_amarillo_max !== null
            || $indicador->rango_rojo_alto_min !== null
            || $indicador->rango_rojo_alto_max !== null;

        if ($hasRanges) {
            return $this->calcularConRangos($resultado, $indicador, $sentido);
        }

        return $this->calcularConMeta($resultado, $metaPeriodo, $sentido);
    }

    private function calcularConRangos(float $resultado, Indicador $indicador, SentidoIndicador $sentido): string
    {
        return match ($sentido) {
            SentidoIndicador::ASCENDENTE => $this->rangoAscendente($resultado, $indicador),
            SentidoIndicador::DESCENDENTE => $this->rangoDescendente($resultado, $indicador),
        };
    }

    private function rangoAscendente(float $resultado, Indicador $indicador): string
    {
        if ($indicador->rango_rojo_alto_min !== null && $resultado >= (float) $indicador->rango_rojo_alto_min) {
            return 'rojo_alto';
        }

        if ($indicador->rango_verde_min !== null && $resultado >= (float) $indicador->rango_verde_min) {
            return 'verde';
        }

        if ($indicador->rango_amarillo_min !== null && $resultado >= (float) $indicador->rango_amarillo_min) {
            return 'amarillo';
        }

        return 'rojo';
    }

    private function rangoDescendente(float $resultado, Indicador $indicador): string
    {
        if ($indicador->rango_rojo_alto_max !== null && $resultado <= (float) $indicador->rango_rojo_alto_max) {
            return 'rojo_alto';
        }

        if ($indicador->rango_verde_max !== null && $resultado <= (float) $indicador->rango_verde_max) {
            return 'verde';
        }

        if ($indicador->rango_amarillo_max !== null && $resultado <= (float) $indicador->rango_amarillo_max) {
            return 'amarillo';
        }

        return 'rojo';
    }

    private function calcularConMeta(float $resultado, ?float $metaPeriodo, SentidoIndicador $sentido): string
    {
        if ($metaPeriodo === null || $metaPeriodo == 0) {
            return 'rojo';
        }

        return match ($sentido) {
            SentidoIndicador::ASCENDENTE => $this->metaAscendente($resultado, $metaPeriodo),
            SentidoIndicador::DESCENDENTE => $this->metaDescendente($resultado, $metaPeriodo),
        };
    }

    private function metaAscendente(float $resultado, float $metaPeriodo): string
    {
        $porcentaje = ($resultado / $metaPeriodo) * 100;
        $umbral = (float) config('tracking.umbral_sobrecumplimiento', 130);

        if ($porcentaje > $umbral) {
            return 'rojo_alto';
        }

        if ($porcentaje >= 90) {
            return 'verde';
        }

        if ($porcentaje >= 70) {
            return 'amarillo';
        }

        return 'rojo';
    }

    private function metaDescendente(float $resultado, float $metaPeriodo): string
    {
        $umbral = (float) config('tracking.umbral_sobrecumplimiento', 130);

        // Espejo simétrico: con U=130 el sobrecumplimiento es resultado < 70% de la meta
        // (30% más allá de la meta en la dirección de mejora).
        if ($resultado < $metaPeriodo * (2 - $umbral / 100)) {
            return 'rojo_alto';
        }

        // For descendente, lower is better, so being at/below meta is good
        if ($resultado <= $metaPeriodo) {
            return 'verde';
        }

        if ($resultado <= $metaPeriodo * 1.3) {
            return 'amarillo';
        }

        return 'rojo';
    }
}
