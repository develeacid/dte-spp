<?php

namespace App\Services\Mml;

use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\ProgramaPresupuestario;

class IndicadorReglasService
{
    public static function tipoPermitido(TipoNivelMir $nivel): array
    {
        return match ($nivel) {
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
        return match ($nivel) {
            TipoNivelMir::FIN, TipoNivelMir::PROPOSITO => TipoIndicador::ESTRATEGICO,
            TipoNivelMir::COMPONENTE => TipoIndicador::ESTRATEGICO,
            TipoNivelMir::ACTIVIDAD => TipoIndicador::GESTION,
        };
    }

    public static function dimensionesPermitidas(TipoNivelMir $nivel): array
    {
        return match ($nivel) {
            TipoNivelMir::FIN => [DimensionIndicador::EFICACIA],
            TipoNivelMir::PROPOSITO => [DimensionIndicador::EFICACIA, DimensionIndicador::EFICIENCIA],
            TipoNivelMir::COMPONENTE => [DimensionIndicador::EFICACIA, DimensionIndicador::EFICIENCIA, DimensionIndicador::CALIDAD],
            TipoNivelMir::ACTIVIDAD => [DimensionIndicador::EFICACIA, DimensionIndicador::EFICIENCIA, DimensionIndicador::ECONOMIA],
        };
    }

    public static function frecuenciasPermitidas(TipoNivelMir $nivel): array
    {
        return match ($nivel) {
            TipoNivelMir::FIN => [FrecuenciaMedicion::ANUAL, FrecuenciaMedicion::BIANUAL, FrecuenciaMedicion::SEXENAL],
            TipoNivelMir::PROPOSITO => [FrecuenciaMedicion::SEMESTRAL, FrecuenciaMedicion::ANUAL],
            TipoNivelMir::COMPONENTE => [FrecuenciaMedicion::TRIMESTRAL, FrecuenciaMedicion::SEMESTRAL],
            TipoNivelMir::ACTIVIDAD => [FrecuenciaMedicion::MENSUAL, FrecuenciaMedicion::TRIMESTRAL],
        };
    }

    /**
     * Regla de diagnóstico (V2-A10): la metodología MIR exige que cada nivel
     * tenga al menos un indicador de dimensión EFICACIA. No se puede bloquear
     * en creación (los indicadores se agregan de uno en uno en el wizard), así
     * que es una advertencia. Reporta los niveles que NO cumplen, incluyendo
     * los que aún no tienen indicadores.
     *
     * Una sola query agrupada (whereDoesntHave) — sin N+1.
     *
     * @return list<array{id:int, tipo_nivel:string, resumen:string}>
     */
    public static function nivelesSinEficacia(ProgramaPresupuestario $programa): array
    {
        return $programa->mirNiveles()
            ->whereDoesntHave('indicadores', function ($query) {
                $query->where('dimension', DimensionIndicador::EFICACIA->value);
            })
            ->orderBy('tipo_nivel')
            ->orderBy('orden')
            ->get()
            ->map(fn ($nivel) => [
                'id' => $nivel->id,
                'tipo_nivel' => $nivel->tipo_nivel->label(),
                'resumen' => $nivel->resumen_narrativo ?? '',
            ])
            ->all();
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
