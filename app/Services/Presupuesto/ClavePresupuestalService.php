<?php

namespace App\Services\Presupuesto;

use App\Models\Presupuesto\ClasificacionFuncional;
use App\Models\ProgramaPresupuestario;

/**
 * Validación de la clave presupuestal canónica CONAC/SEFIP a nivel programa:
 * rangos por segmento (longitud en dígitos) + coherencia jerárquica de la
 * Clasificación Funcional (subfunción ⊂ función ⊂ finalidad).
 */
class ClavePresupuestalService
{
    /** [min, max] por segmento, derivado de la longitud en dígitos SEFIP. */
    private const RANGOS = [
        'grupo' => [0, 9],                 // 1 dígito
        'unidad_responsable' => [0, 99],   // 2 dígitos
        'unidad_ejecutora' => [0, 999],    // 3 dígitos
        'programa_clave' => [0, 999],      // 3 dígitos
        'subprograma' => [0, 99],          // 2 dígitos
        'proyecto' => [0, 999],            // 3 dígitos
        'actividad' => [0, 999],           // 3 dígitos
    ];

    private const ETIQUETAS = [
        'grupo' => 'Grupo',
        'unidad_responsable' => 'Unidad Responsable',
        'unidad_ejecutora' => 'Unidad Ejecutora',
        'programa_clave' => 'Programa',
        'subprograma' => 'Subprograma',
        'proyecto' => 'Proyecto',
        'actividad' => 'Actividad',
    ];

    /** Devuelve un array de mensajes de error; vacío = clave válida. */
    public static function validar(ProgramaPresupuestario $programa): array
    {
        return [
            ...self::validarRangos($programa),
            ...self::validarJerarquiaFuncional($programa),
        ];
    }

    private static function validarRangos(ProgramaPresupuestario $programa): array
    {
        $errores = [];

        foreach (self::RANGOS as $campo => [$min, $max]) {
            $valor = $programa->{$campo};
            if ($valor === null) {
                continue;
            }

            if ($valor < $min || $valor > $max) {
                $errores[] = sprintf(
                    '%s (%d) está fuera de rango. Debe estar entre %d y %d.',
                    self::ETIQUETAS[$campo],
                    $valor,
                    $min,
                    $max,
                );
            }
        }

        return $errores;
    }

    private static function validarJerarquiaFuncional(ProgramaPresupuestario $programa): array
    {
        $errores = [];

        $funcion = $programa->funcion_id ? ClasificacionFuncional::find($programa->funcion_id) : null;
        $subfuncion = $programa->subfuncion_id ? ClasificacionFuncional::find($programa->subfuncion_id) : null;

        if ($funcion !== null) {
            if ($funcion->nivel !== 'funcion') {
                $errores[] = 'La función seleccionada no es una función válida del catálogo.';
            } elseif ($programa->finalidad_id !== null && $funcion->padre_id !== $programa->finalidad_id) {
                $errores[] = 'La función seleccionada no pertenece a la finalidad elegida.';
            }
        }

        if ($subfuncion !== null) {
            if ($subfuncion->nivel !== 'subfuncion') {
                $errores[] = 'La subfunción seleccionada no es una subfunción válida del catálogo.';
            } elseif ($programa->funcion_id !== null && $subfuncion->padre_id !== $programa->funcion_id) {
                $errores[] = 'La subfunción seleccionada no pertenece a la función elegida.';
            }
        }

        return $errores;
    }
}
