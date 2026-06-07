<?php

namespace App\Services\Mml;

use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoIndicador;
use App\Enums\TipoFuenteMv;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
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

    /**
     * Regla B9 (C-073): los MV de indicadores de FIN y PROPÓSITO deben tener
     * fuente externa e independiente (INEGI, CONEVAL, Estadística 911, etc.).
     * COMPONENTES y ACTIVIDADES admiten registros administrativos propios.
     *
     * Función pura: usable desde la UI (MirEditor) y el diagnóstico de import.
     * NULL/'' = sin clasificar (legacy): no bloquea, el diagnóstico lo reporta
     * como advertencia.
     *
     * @return string|null Mensaje de error, o null si es válido.
     */
    public static function validarTipoFuenteMv(TipoNivelMir $nivel, ?string $tipoFuente): ?string
    {
        if ($tipoFuente === null || $tipoFuente === '') {
            return null;
        }

        $esResultado = in_array($nivel, [TipoNivelMir::FIN, TipoNivelMir::PROPOSITO], true);

        if ($esResultado && $tipoFuente !== TipoFuenteMv::EXTERNA->value) {
            return sprintf(
                'Los medios de verificación de %s requieren una fuente externa e independiente (INEGI, CONEVAL, etc.); se indicó "%s".',
                $nivel === TipoNivelMir::FIN ? 'FIN' : 'PROPÓSITO',
                TipoFuenteMv::tryFrom($tipoFuente)?->label() ?? $tipoFuente,
            );
        }

        return null;
    }

    /**
     * Validaciones duras de los rangos de semáforo (4 colores) de un indicador.
     * Reglas B3-B6 (C-066..C-069) del temario MIR. Función pura, sin
     * session/auth: usable tanto desde la UI (Task 3) como desde el
     * diagnóstico (Task 6).
     *
     * Cada regla SOLO aplica sobre valores presentes (null = no capturado, no
     * genera error). Retorna lista de strings de error; vacía = válido.
     *
     * @param  array<string, float|int|null>  $rangos  keys: rango_{verde,amarillo,rojo,rojo_alto}_{min,max}
     * @return list<string>
     */
    public static function validarRangosSemaforo(Indicador $indicador, array $rangos): array
    {
        return self::validarRangosSemaforoPrimitivos(
            $indicador->meta !== null ? (float) $indicador->meta : null,
            $indicador->unidadMedida?->clave,
            $rangos,
        );
    }

    /**
     * Versión pura de validarRangosSemaforo (reglas B3-B6) que opera sobre
     * primitivos en lugar de un modelo Indicador. La usan tanto la UI (vía
     * validarRangosSemaforo, que extrae meta/clave del modelo) como el
     * diagnóstico de import (MirDiagnosticoService), que trabaja con un DTO.
     *
     * @param  array<string, float|int|null>  $rangos  keys: rango_{verde,amarillo,rojo,rojo_alto}_{min,max}
     * @return list<string>
     */
    public static function validarRangosSemaforoPrimitivos(?float $meta, ?string $claveUnidad, array $rangos): array
    {
        $errores = [];

        $colores = [
            'verde' => 'rango_verde',
            'amarillo' => 'rango_amarillo',
            'rojo' => 'rango_rojo',
            'rojo alto' => 'rango_rojo_alto',
        ];

        $valor = static fn (?string $key): ?float => isset($rangos[$key]) && $rangos[$key] !== null
            ? (float) $rangos[$key]
            : null;

        // B3 (C-066): la meta anual debe caer dentro del rango verde.
        $verdeMin = $valor('rango_verde_min');
        $verdeMax = $valor('rango_verde_max');
        if ($meta !== null && $verdeMin !== null && $verdeMax !== null) {
            if ($meta < $verdeMin || $meta > $verdeMax) {
                $errores[] = sprintf(
                    'La meta anual (%s) debe caer dentro del rango verde [%s, %s].',
                    self::fmt($meta),
                    self::fmt($verdeMin),
                    self::fmt($verdeMax),
                );
            }
        }

        // B4 (C-067): pre-condición min<=max por color + sin solapamiento.
        $intervalos = [];
        foreach ($colores as $etiqueta => $prefijo) {
            $min = $valor("{$prefijo}_min");
            $max = $valor("{$prefijo}_max");
            if ($min === null || $max === null) {
                continue;
            }
            if ($min > $max) {
                $errores[] = sprintf('El rango %s tiene mínimo mayor que máximo.', $etiqueta);

                continue;
            }
            $intervalos[$etiqueta] = [$min, $max];
        }

        $etiquetas = array_keys($intervalos);
        for ($i = 0; $i < count($etiquetas); $i++) {
            for ($j = $i + 1; $j < count($etiquetas); $j++) {
                [$aMin, $aMax] = $intervalos[$etiquetas[$i]];
                [$bMin, $bMax] = $intervalos[$etiquetas[$j]];
                // Intersección con longitud > 0 (bordes contiguos son válidos).
                $inicio = max($aMin, $bMin);
                $fin = min($aMax, $bMax);
                if ($inicio < $fin) {
                    $errores[] = sprintf(
                        'Los rangos %s y %s se solapan.',
                        $etiquetas[$i],
                        $etiquetas[$j],
                    );
                }
            }
        }

        // B5 (C-068): con unidad Porcentaje todo límite debe estar en [0, 100].
        if ($claveUnidad === 'PCT') {
            foreach ($colores as $prefijo) {
                foreach (['min', 'max'] as $extremo) {
                    $campo = "{$prefijo}_{$extremo}";
                    $v = $valor($campo);
                    if ($v !== null && ($v < 0 || $v > 100)) {
                        $errores[] = sprintf(
                            'Con unidad Porcentaje los límites del semáforo deben estar entre 0 y 100 (%s=%s).',
                            $campo,
                            self::fmt($v),
                        );
                    }
                }
            }
        }

        // B6 (C-069): el rango rojo no puede iniciar en cero.
        $rojoMin = $valor('rango_rojo_min');
        if ($rojoMin !== null && $rojoMin === 0.0) {
            $errores[] = 'El rango rojo no puede iniciar en cero (C-069).';
        }

        return $errores;
    }

    private static function fmt(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 4, '.', ''), '0'), '.');
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
