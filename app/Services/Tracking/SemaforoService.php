<?php

namespace App\Services\Tracking;

use App\Enums\SentidoIndicador;
use App\Models\Mml\Indicador;

class SemaforoService
{
    /**
     * Calculate traffic light color based on resultado vs indicator ranges.
     *
     * @return string verde|amarillo|rojo
     */
    public function calcular(float $resultado, Indicador $indicador, ?float $metaPeriodo = null): string
    {
        $sentido = $indicador->sentido ?? SentidoIndicador::ASCENDENTE;

        $hasRanges = $indicador->rango_verde_min !== null
            || $indicador->rango_verde_max !== null
            || $indicador->rango_amarillo_min !== null
            || $indicador->rango_amarillo_max !== null;

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
            SentidoIndicador::REGULAR => $this->rangoRegular($resultado, $indicador),
        };
    }

    private function rangoAscendente(float $resultado, Indicador $indicador): string
    {
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
        if ($indicador->rango_verde_max !== null && $resultado <= (float) $indicador->rango_verde_max) {
            return 'verde';
        }

        if ($indicador->rango_amarillo_max !== null && $resultado <= (float) $indicador->rango_amarillo_max) {
            return 'amarillo';
        }

        return 'rojo';
    }

    private function rangoRegular(float $resultado, Indicador $indicador): string
    {
        $verdeMin = $indicador->rango_verde_min !== null ? (float) $indicador->rango_verde_min : null;
        $verdeMax = $indicador->rango_verde_max !== null ? (float) $indicador->rango_verde_max : null;

        if ($verdeMin !== null && $verdeMax !== null && $resultado >= $verdeMin && $resultado <= $verdeMax) {
            return 'verde';
        }

        $amarilloMin = $indicador->rango_amarillo_min !== null ? (float) $indicador->rango_amarillo_min : null;
        $amarilloMax = $indicador->rango_amarillo_max !== null ? (float) $indicador->rango_amarillo_max : null;

        if ($amarilloMin !== null && $amarilloMax !== null && $resultado >= $amarilloMin && $resultado <= $amarilloMax) {
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
            SentidoIndicador::REGULAR => $this->metaRegular($resultado, $metaPeriodo),
        };
    }

    private function metaAscendente(float $resultado, float $metaPeriodo): string
    {
        $porcentaje = ($resultado / $metaPeriodo) * 100;

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
        // For descendente, lower is better, so being at/below meta is good
        if ($resultado <= $metaPeriodo) {
            return 'verde';
        }

        if ($resultado <= $metaPeriodo * 1.3) {
            return 'amarillo';
        }

        return 'rojo';
    }

    private function metaRegular(float $resultado, float $metaPeriodo): string
    {
        $diff = abs($resultado - $metaPeriodo);
        $tolerance = abs($metaPeriodo) * 0.1;

        if ($diff <= $tolerance) {
            return 'verde';
        }

        if ($diff <= $tolerance * 3) {
            return 'amarillo';
        }

        return 'rojo';
    }
}
