<?php

namespace App\Services\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\CremaaValidacion;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\Mml\MirVersion;
use App\Models\ProgramaPresupuestario;

class MirSnapshotService
{
    public function crear(ProgramaPresupuestario $programa, string $etiqueta, int $userId): MirVersion
    {
        $snapshot = $this->serializarMir($programa);

        return MirVersion::create([
            'programa_presupuestario_id' => $programa->id,
            'etiqueta' => $etiqueta,
            'snapshot' => $snapshot,
            'created_by' => $userId,
        ]);
    }

    public function restaurar(MirVersion $version): void
    {
        $programa = $version->programa;

        // Delete current MIR (children first due to FKs)
        $programa->mirNiveles()->each(function (MirNivel $nivel) {
            $nivel->indicadores()->each(function (Indicador $indicador) {
                $indicador->variables()->delete();
                $indicador->mediosVerificacion()->delete();
                $indicador->cremaaValidacion()?->delete();
            });
            $nivel->indicadores()->delete();
        });
        $programa->mirNiveles()->delete();

        // Restore from snapshot
        $this->deserializarMir($programa, $version->snapshot);
    }

    private function serializarMir(ProgramaPresupuestario $programa): array
    {
        return $programa->mirNiveles()
            ->with(['indicadores.variables', 'indicadores.mediosVerificacion', 'indicadores.cremaaValidacion'])
            ->orderBy('tipo_nivel')
            ->orderBy('orden')
            ->get()
            ->map(fn (MirNivel $nivel) => [
                'tipo_nivel' => $nivel->tipo_nivel->value ?? $nivel->tipo_nivel,
                'resumen_narrativo' => $nivel->resumen_narrativo,
                'supuestos' => $nivel->supuestos,
                'orden' => $nivel->orden,
                'arbol_nodo_id' => $nivel->arbol_nodo_id,
                'componente_orden' => $nivel->componente_id
                    ? MirNivel::find($nivel->componente_id)?->orden
                    : null,
                'ped_objetivo_estrategico_id' => $nivel->ped_objetivo_estrategico_id,
                'programa_derivado_objetivo_id' => $nivel->programa_derivado_objetivo_id,
                'ped_linea_accion_id' => $nivel->ped_linea_accion_id,
                'indicadores' => $nivel->indicadores->map(fn (Indicador $ind) => [
                    'nombre' => $ind->nombre,
                    'formula_texto' => $ind->formula_texto,
                    'tipo' => $ind->tipo?->value,
                    'dimension' => $ind->dimension?->value,
                    'frecuencia' => $ind->frecuencia?->value,
                    'sentido' => $ind->sentido?->value,
                    'linea_base' => $ind->linea_base,
                    'meta' => $ind->meta,
                    'orden' => $ind->orden,
                    'variables' => $ind->variables->map(fn (IndicadorVariable $v) => [
                        'simbolo' => $v->simbolo,
                        'nombre' => $v->nombre,
                        'descripcion' => $v->descripcion,
                        'comportamiento' => $v->comportamiento,
                        'unidad_medida_id' => $v->unidad_medida_id,
                        'orden' => $v->orden,
                    ])->toArray(),
                    'medios' => $ind->mediosVerificacion->map(fn (MedioVerificacion $m) => [
                        'nombre' => $m->nombre,
                        'fuente' => $m->fuente,
                        'orden' => $m->orden,
                    ])->toArray(),
                    'cremaa' => $ind->cremaaValidacion ? [
                        'claro' => $ind->cremaaValidacion->claro,
                        'claro_observacion' => $ind->cremaaValidacion->claro_observacion,
                        'relevante' => $ind->cremaaValidacion->relevante,
                        'relevante_observacion' => $ind->cremaaValidacion->relevante_observacion,
                        'economico' => $ind->cremaaValidacion->economico,
                        'economico_observacion' => $ind->cremaaValidacion->economico_observacion,
                        'monitoreable' => $ind->cremaaValidacion->monitoreable,
                        'monitoreable_observacion' => $ind->cremaaValidacion->monitoreable_observacion,
                        'adecuado' => $ind->cremaaValidacion->adecuado,
                        'adecuado_observacion' => $ind->cremaaValidacion->adecuado_observacion,
                        'aportante' => $ind->cremaaValidacion->aportante,
                        'aportante_observacion' => $ind->cremaaValidacion->aportante_observacion,
                    ] : null,
                ])->toArray(),
            ])->toArray();
    }

    private function deserializarMir(ProgramaPresupuestario $programa, array $snapshot): void
    {
        // First pass: create all niveles and track componente mapping
        $componenteMap = []; // orden => new id

        foreach ($snapshot as $nivelData) {
            if ($nivelData['tipo_nivel'] === TipoNivelMir::COMPONENTE->value) {
                $nivel = MirNivel::create([
                    'programa_presupuestario_id' => $programa->id,
                    'tipo_nivel' => $nivelData['tipo_nivel'],
                    'resumen_narrativo' => $nivelData['resumen_narrativo'],
                    'supuestos' => $nivelData['supuestos'],
                    'orden' => $nivelData['orden'],
                    'arbol_nodo_id' => $nivelData['arbol_nodo_id'] ?? null,
                    'ped_objetivo_estrategico_id' => $nivelData['ped_objetivo_estrategico_id'] ?? null,
                    'programa_derivado_objetivo_id' => $nivelData['programa_derivado_objetivo_id'] ?? null,
                    'ped_linea_accion_id' => $nivelData['ped_linea_accion_id'] ?? null,
                ]);
                $componenteMap[$nivelData['orden']] = $nivel->id;
                $this->restaurarIndicadores($nivel, $nivelData['indicadores'] ?? []);
            }
        }

        // Second pass: create non-componente niveles
        foreach ($snapshot as $nivelData) {
            if ($nivelData['tipo_nivel'] === TipoNivelMir::COMPONENTE->value) {
                continue;
            }

            $componenteId = null;
            if ($nivelData['tipo_nivel'] === TipoNivelMir::ACTIVIDAD->value && $nivelData['componente_orden'] !== null) {
                $componenteId = $componenteMap[$nivelData['componente_orden']] ?? null;
            }

            $nivel = MirNivel::create([
                'programa_presupuestario_id' => $programa->id,
                'tipo_nivel' => $nivelData['tipo_nivel'],
                'resumen_narrativo' => $nivelData['resumen_narrativo'],
                'supuestos' => $nivelData['supuestos'],
                'orden' => $nivelData['orden'],
                'componente_id' => $componenteId,
                'arbol_nodo_id' => $nivelData['arbol_nodo_id'] ?? null,
                'ped_objetivo_estrategico_id' => $nivelData['ped_objetivo_estrategico_id'] ?? null,
                'programa_derivado_objetivo_id' => $nivelData['programa_derivado_objetivo_id'] ?? null,
                'ped_linea_accion_id' => $nivelData['ped_linea_accion_id'] ?? null,
            ]);

            $this->restaurarIndicadores($nivel, $nivelData['indicadores'] ?? []);
        }
    }

    private function restaurarIndicadores(MirNivel $nivel, array $indicadoresData): void
    {
        foreach ($indicadoresData as $indData) {
            $indicador = Indicador::create([
                'mir_nivel_id' => $nivel->id,
                'nombre' => $indData['nombre'],
                'formula_texto' => $indData['formula_texto'] ?? null,
                'tipo' => $indData['tipo'],
                'dimension' => $indData['dimension'],
                'frecuencia' => $indData['frecuencia'],
                'sentido' => $indData['sentido'] ?? null,
                'linea_base' => $indData['linea_base'] ?? null,
                'meta' => $indData['meta'] ?? null,
                'orden' => $indData['orden'],
            ]);

            foreach ($indData['variables'] ?? [] as $varData) {
                IndicadorVariable::create([
                    'indicador_id' => $indicador->id,
                    'simbolo' => $varData['simbolo'],
                    'nombre' => $varData['nombre'],
                    'descripcion' => $varData['descripcion'] ?? null,
                    'comportamiento' => $varData['comportamiento'] ?? null,
                    'unidad_medida_id' => $varData['unidad_medida_id'] ?? null,
                    'orden' => $varData['orden'],
                ]);
            }

            foreach ($indData['medios'] ?? [] as $medioData) {
                MedioVerificacion::create([
                    'indicador_id' => $indicador->id,
                    'nombre' => $medioData['nombre'],
                    'fuente' => $medioData['fuente'] ?? null,
                    'orden' => $medioData['orden'],
                ]);
            }

            if (! empty($indData['cremaa'])) {
                CremaaValidacion::create(array_merge(
                    ['indicador_id' => $indicador->id],
                    $indData['cremaa']
                ));
            }
        }
    }
}
