<?php

namespace App\Services\Mml;

use App\DTOs\ImportedMirData;
use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\SentidoIndicador;
use App\Enums\TipoIndicador;
use App\Enums\TipoNivelMir;

class MirDiagnosticoService
{
    /**
     * Run diagnosis on imported MIR data and return an array of gaps.
     *
     * Each gap: [nivel_idx, indicador_idx, campo, severidad, mensaje]
     *
     * @return array<int, array{nivel_idx: int, indicador_idx: int|null, campo: string, severidad: string, mensaje: string}>
     */
    public function diagnosticar(ImportedMirData $data): array
    {
        $gaps = [];

        foreach ($data->niveles as $ni => $nivel) {
            // Nivel-level checks
            if (empty($nivel['resumen_narrativo'])) {
                $gaps[] = $this->gap($ni, null, 'resumen_narrativo', 'critico', 'Falta el resumen narrativo.');
            }

            if (empty($nivel['supuestos'])) {
                $gaps[] = $this->gap($ni, null, 'supuestos', 'menor', 'No se especificaron supuestos.');
            }

            // Check tipo_nivel is valid
            if (! empty($nivel['tipo_nivel'])) {
                $validNiveles = TipoNivelMir::values();
                if (! in_array($nivel['tipo_nivel'], $validNiveles, true)) {
                    $gaps[] = $this->gap($ni, null, 'tipo_nivel', 'advertencia', "Tipo de nivel no reconocido: \"{$nivel['tipo_nivel']}\".");
                }
            }

            // Must have at least one indicator
            if (empty($nivel['indicadores'])) {
                $gaps[] = $this->gap($ni, null, 'indicadores', 'critico', 'El nivel no tiene indicadores.');
            }

            // Indicator-level checks
            foreach ($nivel['indicadores'] ?? [] as $ii => $ind) {
                // Critical fields
                if (empty($ind['formula_texto'])) {
                    $gaps[] = $this->gap($ni, $ii, 'formula_texto', 'critico', 'Falta la fórmula del indicador.');
                }
                if (empty($ind['tipo'])) {
                    $gaps[] = $this->gap($ni, $ii, 'tipo', 'critico', 'Falta el tipo de indicador.');
                }
                if (empty($ind['dimension'])) {
                    $gaps[] = $this->gap($ni, $ii, 'dimension', 'critico', 'Falta la dimensión del indicador.');
                }
                if (empty($ind['frecuencia'])) {
                    $gaps[] = $this->gap($ni, $ii, 'frecuencia', 'critico', 'Falta la frecuencia de medición.');
                }
                if (empty($ind['medios'])) {
                    $gaps[] = $this->gap($ni, $ii, 'medios', 'critico', 'No se especificaron medios de verificación.');
                }

                // Minor fields
                if (empty($ind['sentido'])) {
                    $gaps[] = $this->gap($ni, $ii, 'sentido', 'menor', 'Falta el sentido del indicador.');
                }
                if (! isset($ind['linea_base']) || $ind['linea_base'] === null) {
                    $gaps[] = $this->gap($ni, $ii, 'linea_base', 'menor', 'Falta la línea base.');
                }
                if (! isset($ind['meta']) || $ind['meta'] === null) {
                    $gaps[] = $this->gap($ni, $ii, 'meta', 'menor', 'Falta la meta.');
                }
                if (empty($ind['rangos_semaforo'])) {
                    $gaps[] = $this->gap($ni, $ii, 'rangos_semaforo', 'menor', 'No se definieron rangos de semáforo.');
                }

                // Warning: unrecognized enum values
                if (! empty($ind['tipo']) && ! in_array($ind['tipo'], TipoIndicador::values(), true)) {
                    $gaps[] = $this->gap($ni, $ii, 'tipo', 'advertencia', "Tipo de indicador no reconocido: \"{$ind['tipo']}\".");
                }
                if (! empty($ind['dimension']) && ! in_array($ind['dimension'], DimensionIndicador::values(), true)) {
                    $gaps[] = $this->gap($ni, $ii, 'dimension', 'advertencia', "Dimensión no reconocida: \"{$ind['dimension']}\".");
                }
                if (! empty($ind['frecuencia']) && ! in_array($ind['frecuencia'], FrecuenciaMedicion::values(), true)) {
                    $gaps[] = $this->gap($ni, $ii, 'frecuencia', 'advertencia', "Frecuencia no reconocida: \"{$ind['frecuencia']}\".");
                }
                if (! empty($ind['sentido']) && ! in_array($ind['sentido'], SentidoIndicador::values(), true)) {
                    $gaps[] = $this->gap($ni, $ii, 'sentido', 'advertencia', "Sentido no reconocido: \"{$ind['sentido']}\".");
                }
            }
        }

        return $gaps;
    }

    /**
     * Count gaps by severity.
     *
     * @return array{critico: int, menor: int, advertencia: int, total: int}
     */
    public function conteo(array $diagnostico): array
    {
        $counts = ['critico' => 0, 'menor' => 0, 'advertencia' => 0];

        foreach ($diagnostico as $gap) {
            $sev = $gap['severidad'] ?? 'advertencia';
            if (isset($counts[$sev])) {
                $counts[$sev]++;
            }
        }

        $counts['total'] = array_sum($counts);

        return $counts;
    }

    private function gap(int $nivelIdx, ?int $indicadorIdx, string $campo, string $severidad, string $mensaje): array
    {
        return [
            'nivel_idx' => $nivelIdx,
            'indicador_idx' => $indicadorIdx,
            'campo' => $campo,
            'severidad' => $severidad,
            'mensaje' => $mensaje,
        ];
    }
}
