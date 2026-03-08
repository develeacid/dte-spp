<?php

namespace App\Services\Mml;

use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoIndicador;
use App\Enums\TipoNivelMir;

class IndicadorReglasService
{
    public static function tipoPermitido(TipoNivelMir $nivel): array
    {
        return match($nivel) {
            TipoNivelMir::FIN, TipoNivelMir::PROPOSITO => [TipoIndicador::ESTRATEGICO],
            TipoNivelMir::COMPONENTE => [TipoIndicador::ESTRATEGICO, TipoIndicador::GESTION],
            TipoNivelMir::ACTIVIDAD => [TipoIndicador::GESTION],
        };
    }

    public static function esTipoFijo(TipoNivelMir $nivel): bool
    {
        return $nivel !== TipoNivelMir::COMPONENTE;
    }

    public static function tipoDefault(TipoNivelMir $nivel): TipoIndicador
    {
        return match($nivel) {
            TipoNivelMir::FIN, TipoNivelMir::PROPOSITO => TipoIndicador::ESTRATEGICO,
            TipoNivelMir::COMPONENTE => TipoIndicador::ESTRATEGICO,
            TipoNivelMir::ACTIVIDAD => TipoIndicador::GESTION,
        };
    }

    public static function dimensionesPermitidas(TipoNivelMir $nivel): array
    {
        return match($nivel) {
            TipoNivelMir::FIN => [DimensionIndicador::EFICACIA],
            TipoNivelMir::PROPOSITO => [DimensionIndicador::EFICACIA, DimensionIndicador::EFICIENCIA],
            TipoNivelMir::COMPONENTE => [DimensionIndicador::EFICACIA, DimensionIndicador::EFICIENCIA, DimensionIndicador::CALIDAD],
            TipoNivelMir::ACTIVIDAD => [DimensionIndicador::EFICACIA, DimensionIndicador::EFICIENCIA, DimensionIndicador::ECONOMIA],
        };
    }

    public static function frecuenciasPermitidas(TipoNivelMir $nivel): array
    {
        return match($nivel) {
            TipoNivelMir::FIN => [FrecuenciaMedicion::ANUAL, FrecuenciaMedicion::BIANUAL, FrecuenciaMedicion::SEXENAL],
            TipoNivelMir::PROPOSITO => [FrecuenciaMedicion::SEMESTRAL, FrecuenciaMedicion::ANUAL],
            TipoNivelMir::COMPONENTE => [FrecuenciaMedicion::TRIMESTRAL, FrecuenciaMedicion::SEMESTRAL],
            TipoNivelMir::ACTIVIDAD => [FrecuenciaMedicion::MENSUAL, FrecuenciaMedicion::TRIMESTRAL],
        };
    }

    public static function reglasParaNivel(TipoNivelMir $nivel): array
    {
        return [
            'tipo_fijo' => self::esTipoFijo($nivel),
            'tipo_default' => self::tipoDefault($nivel)->value,
            'tipos' => array_map(fn ($t) => $t->value, self::tipoPermitido($nivel)),
            'dimensiones' => array_map(fn ($d) => $d->value, self::dimensionesPermitidas($nivel)),
            'frecuencias' => array_map(fn ($f) => $f->value, self::frecuenciasPermitidas($nivel)),
        ];
    }
}
