<?php

namespace App\Livewire\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\ImportacionReporte;
use App\Models\Mml\MirNivel;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Services\Embeddings\SemanticSearchService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Vincular Alineación — MIR Importada')]
class VincularAlineacion extends Component
{
    public ImportacionReporte $reporte;

    public int $pasoActual = 0;

    /** @var array Suggestions from semantic search */
    public array $sugerencias = [];

    /** @var array Ordered MirNivel IDs to step through */
    public array $nivelesIds = [];

    public function mount(ImportacionReporte $importacion): void
    {
        $this->reporte = $importacion;

        $this->nivelesIds = MirNivel::where('programa_presupuestario_id', $this->reporte->programa_presupuestario_id)
            ->orderByRaw("CASE tipo_nivel
                WHEN 'fin' THEN 1
                WHEN 'proposito' THEN 2
                WHEN 'componente' THEN 3
                WHEN 'actividad' THEN 4
                ELSE 5
            END")
            ->orderBy('orden')
            ->pluck('id')
            ->toArray();
    }

    public function buscar(): void
    {
        $nivel = $this->nivelActual();

        if (! $nivel || empty($nivel->resumen_narrativo)) {
            $this->sugerencias = [];

            return;
        }

        try {
            $search = app(SemanticSearchService::class);

            if (in_array($nivel->tipo_nivel, [TipoNivelMir::FIN, TipoNivelMir::PROPOSITO])) {
                $results = $search->findSimilar(
                    $nivel->resumen_narrativo,
                    PedObjetivoEstrategico::class,
                    5
                );
            } else {
                $results = $search->findSimilar(
                    $nivel->resumen_narrativo,
                    PedLineaAccion::class,
                    5
                );
            }

            $this->sugerencias = $results->map(fn ($r) => [
                'id' => $r->model->id,
                'tipo' => class_basename($r->model),
                'texto' => $r->model->descripcion ?? $r->model->nombre ?? '',
                'score' => round($r->score * 100, 1),
                'alta_confianza' => $r->score >= 0.85,
            ])->toArray();
        } catch (\Exception $e) {
            $this->sugerencias = [];
        }
    }

    public function seleccionar(int $entidadId, string $tipo): void
    {
        $nivel = $this->nivelActual();

        if (! $nivel) {
            return;
        }

        $updateData = [];

        if ($tipo === 'PedObjetivoEstrategico') {
            $updateData['ped_objetivo_estrategico_id'] = $entidadId;
        } elseif ($tipo === 'PedLineaAccion') {
            $updateData['ped_linea_accion_id'] = $entidadId;
        }

        if (! empty($updateData)) {
            $nivel->update($updateData);
        }

        $this->sugerencias = [];
        $this->avanzar();
    }

    public function omitir(): void
    {
        $this->sugerencias = [];
        $this->avanzar();
    }

    public function anterior(): void
    {
        if ($this->pasoActual > 0) {
            $this->pasoActual--;
            $this->sugerencias = [];
        }
    }

    public function finalizar(): void
    {
        $this->redirect(route('mml.importar.calendarizar', ['importacion' => $this->reporte->id]));
    }

    public function render()
    {
        $nivelActual = $this->nivelActual();

        $niveles = MirNivel::whereIn('id', $this->nivelesIds)
            ->with(['pedObjetivoEstrategico', 'pedLineaAccion'])
            ->orderByRaw("CASE tipo_nivel
                WHEN 'fin' THEN 1
                WHEN 'proposito' THEN 2
                WHEN 'componente' THEN 3
                WHEN 'actividad' THEN 4
                ELSE 5
            END")
            ->orderBy('orden')
            ->get()
            ->map(fn (MirNivel $n) => [
                'id' => $n->id,
                'tipo_nivel' => $n->tipo_nivel->value,
                'tipo_nivel_label' => $n->tipo_nivel->label(),
                'color_class' => $n->tipo_nivel->colorClass(),
                'resumen_narrativo' => $n->resumen_narrativo,
                'vinculado' => $n->ped_objetivo_estrategico_id !== null || $n->ped_linea_accion_id !== null,
                'vinculacion_texto' => $n->pedObjetivoEstrategico?->descripcion ?? $n->pedLineaAccion?->descripcion ?? null,
            ])
            ->toArray();

        return view('livewire.mml.vincular-alineacion', [
            'nivelActual' => $nivelActual ? [
                'id' => $nivelActual->id,
                'tipo_nivel' => $nivelActual->tipo_nivel->value,
                'tipo_nivel_label' => $nivelActual->tipo_nivel->label(),
                'color_class' => $nivelActual->tipo_nivel->colorClass(),
                'resumen_narrativo' => $nivelActual->resumen_narrativo,
                'vinculado' => $nivelActual->ped_objetivo_estrategico_id !== null || $nivelActual->ped_linea_accion_id !== null,
            ] : null,
            'niveles' => $niveles,
            'totalPasos' => count($this->nivelesIds),
        ]);
    }

    private function nivelActual(): ?MirNivel
    {
        if (! isset($this->nivelesIds[$this->pasoActual])) {
            return null;
        }

        return MirNivel::find($this->nivelesIds[$this->pasoActual]);
    }

    private function avanzar(): void
    {
        if ($this->pasoActual < count($this->nivelesIds) - 1) {
            $this->pasoActual++;
        }
    }
}
