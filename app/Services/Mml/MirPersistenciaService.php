<?php

namespace App\Services\Mml;

use App\DTOs\ImportedMirData;
use App\Enums\DimensionIndicador;
use App\Enums\EstadoPrograma;
use App\Enums\FrecuenciaMedicion;
use App\Enums\OrigenPrograma;
use App\Enums\SentidoIndicador;
use App\Enums\TipoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use Illuminate\Support\Facades\DB;

class MirPersistenciaService
{
    /**
     * Accented variant mapping for enum normalization.
     */
    private const ACCENT_MAP = [
        'estratégico' => 'estrategico',
        'gestión'     => 'gestion',
        'economía'    => 'economia',
    ];

    /**
     * Persist ImportedMirData into the database, creating a ProgramaPresupuestario
     * with its full MIR hierarchy (niveles, indicadores, variables, medios).
     *
     * @param  ImportedMirData  $data
     * @param  int              $teamId
     * @param  int              $userId
     * @param  array|null       $diagnostico  Gaps array from MirDiagnosticoService
     * @return ProgramaPresupuestario
     */
    public function persistir(ImportedMirData $data, int $teamId, int $userId, ?array $diagnostico = null): ProgramaPresupuestario
    {
        return DB::transaction(function () use ($data, $teamId, $userId, $diagnostico) {
            $programa = ProgramaPresupuestario::create([
                'nombre' => $data->nombre ?? 'Programa importado',
                'clave' => $data->clave ?? 'IMP-' . now()->format('YmdHis'),
                'team_id' => $teamId,
                'ejercicio_fiscal' => $data->ejercicioFiscal ?? (int) date('Y'),
                'origen' => OrigenPrograma::IMPORTADO,
                'estado' => EstadoPrograma::BORRADOR,
                'created_by' => $userId,
            ]);

            // Index critical gaps by (nivel_idx, indicador_idx) for quick lookup
            $criticalGaps = $this->indexCriticalGaps($diagnostico ?? []);

            // Two-pass: componentes first, then the rest
            $componenteMap = []; // componente_idx (from DTO) => new MirNivel id

            // First pass: create componentes
            foreach ($data->niveles as $ni => $nivelData) {
                $tipoNivel = $this->resolveEnum(TipoNivelMir::class, $nivelData['tipo_nivel'] ?? '');

                if ($tipoNivel === TipoNivelMir::COMPONENTE) {
                    $nivel = $this->crearNivel($programa, $nivelData, $tipoNivel, null);
                    $componenteMap[$ni] = $nivel->id;
                    $this->crearIndicadores($nivel, $nivelData['indicadores'] ?? [], $ni, $criticalGaps);
                }
            }

            // Second pass: non-componente niveles
            foreach ($data->niveles as $ni => $nivelData) {
                $tipoNivel = $this->resolveEnum(TipoNivelMir::class, $nivelData['tipo_nivel'] ?? '');

                if ($tipoNivel === TipoNivelMir::COMPONENTE) {
                    continue;
                }

                $componenteId = null;
                if ($tipoNivel === TipoNivelMir::ACTIVIDAD && isset($nivelData['componente_idx'])) {
                    $componenteId = $componenteMap[$nivelData['componente_idx']] ?? null;
                }

                $nivel = $this->crearNivel($programa, $nivelData, $tipoNivel, $componenteId);
                $this->crearIndicadores($nivel, $nivelData['indicadores'] ?? [], $ni, $criticalGaps);
            }

            return $programa;
        });
    }

    private function crearNivel(
        ProgramaPresupuestario $programa,
        array $nivelData,
        ?TipoNivelMir $tipoNivel,
        ?int $componenteId,
    ): MirNivel {
        return MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => $tipoNivel?->value ?? $nivelData['tipo_nivel'] ?? 'fin',
            'resumen_narrativo' => $nivelData['resumen_narrativo'] ?? null,
            'supuestos' => $nivelData['supuestos'] ?? null,
            'orden' => $nivelData['orden'] ?? 0,
            'componente_id' => $componenteId,
        ]);
    }

    private function crearIndicadores(MirNivel $nivel, array $indicadoresData, int $nivelIdx, array $criticalGaps): void
    {
        foreach ($indicadoresData as $ii => $indData) {
            $hasCriticalGap = isset($criticalGaps["{$nivelIdx}.{$ii}"]);

            $indicador = Indicador::create([
                'mir_nivel_id' => $nivel->id,
                'nombre' => $indData['nombre'] ?? 'Sin nombre',
                'formula_texto' => $indData['formula_texto'] ?? null,
                'tipo' => $this->resolveEnum(TipoIndicador::class, $indData['tipo'] ?? '')?->value,
                'dimension' => $this->resolveEnum(DimensionIndicador::class, $indData['dimension'] ?? '')?->value,
                'frecuencia' => $this->resolveEnum(FrecuenciaMedicion::class, $indData['frecuencia'] ?? '')?->value,
                'sentido' => $this->resolveEnum(SentidoIndicador::class, $indData['sentido'] ?? '')?->value,
                'linea_base' => $indData['linea_base'] ?? null,
                'meta' => $indData['meta'] ?? null,
                'orden' => $indData['orden'] ?? $ii,
                'activo_seguimiento' => !$hasCriticalGap,
            ]);

            // Create variables
            foreach ($indData['variables'] ?? [] as $vi => $varData) {
                IndicadorVariable::create([
                    'indicador_id' => $indicador->id,
                    'simbolo' => $varData['simbolo'] ?? '',
                    'nombre' => $varData['nombre'] ?? '',
                    'orden' => $vi,
                ]);
            }

            // Create medios de verificacion
            foreach ($indData['medios'] ?? [] as $mi => $medioData) {
                MedioVerificacion::create([
                    'indicador_id' => $indicador->id,
                    'nombre' => $medioData['nombre'] ?? '',
                    'fuente' => $medioData['fuente'] ?? null,
                    'orden' => $mi,
                ]);
            }
        }
    }

    /**
     * Build a lookup of indicator-level critical gaps: "nivelIdx.indicadorIdx" => true
     */
    private function indexCriticalGaps(array $diagnostico): array
    {
        $index = [];

        foreach ($diagnostico as $gap) {
            if (($gap['severidad'] ?? '') === 'critico' && $gap['indicador_idx'] !== null) {
                $key = "{$gap['nivel_idx']}.{$gap['indicador_idx']}";
                $index[$key] = true;
            }
        }

        return $index;
    }

    /**
     * Try to resolve a string to an enum value, handling accented variants.
     *
     * @template T of \BackedEnum
     * @param  class-string<T>  $enumClass
     * @param  string           $value
     * @return T|null
     */
    private function resolveEnum(string $enumClass, string $value): mixed
    {
        if ($value === '' || $value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($value));

        // Check accent map
        if (isset(self::ACCENT_MAP[$normalized])) {
            $normalized = self::ACCENT_MAP[$normalized];
        }

        return $enumClass::tryFrom($normalized);
    }
}
