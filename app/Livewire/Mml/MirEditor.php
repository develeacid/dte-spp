<?php

namespace App\Livewire\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\TipoNivelMir;
use App\Models\Mml\CremaaValidacion;
use App\Models\Mml\Indicador;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Services\Embeddings\SemanticSearchService;
use App\Services\Mml\IndicadorReglasService;
use App\Services\Mml\MirLogicaValidacionService;
use App\Services\Mml\MirPrellenadoService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Etapa 5 — Matriz de Indicadores para Resultados')]
class MirEditor extends Component
{
    public ProgramaPresupuestario $programa;
    public array $hallazgosLogica = [];
    public bool $validacionLogicaEjecutada = false;
    public array $sugerenciasAlineacion = [];
    public ?int $nivelAlineacionActivo = null;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;

        // Prellenar desde EAP si no hay niveles
        (new MirPrellenadoService())->prellenar($programa);
    }

    public function guardarNivel(int $nivelId, string $campo, string $valor): void
    {
        $nivel = MirNivel::findOrFail($nivelId);

        if (in_array($campo, ['resumen_narrativo', 'supuestos'])) {
            $nivel->update([$campo => $valor]);
        }
    }

    public function agregarComponente(): void
    {
        $maxOrden = $this->programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE->value)
            ->max('orden') ?? 0;

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'orden' => $maxOrden + 1,
        ]);
    }

    public function agregarActividad(int $componenteId): void
    {
        $maxOrden = MirNivel::where('componente_id', $componenteId)->max('orden') ?? 0;

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'componente_id' => $componenteId,
            'orden' => $maxOrden + 1,
        ]);
    }

    public function eliminarNivel(int $nivelId): void
    {
        $nivel = MirNivel::findOrFail($nivelId);

        // Only allow deleting Componente/Actividad (not Fin/Propósito)
        if (in_array($nivel->tipo_nivel, [TipoNivelMir::COMPONENTE, TipoNivelMir::ACTIVIDAD])) {
            $nivel->delete();
        }
    }

    public function agregarIndicador(int $nivelId): void
    {
        $nivel = MirNivel::findOrFail($nivelId);
        $reglas = IndicadorReglasService::reglasParaNivel($nivel->tipo_nivel);
        $maxOrden = $nivel->indicadores()->max('orden') ?? 0;

        Indicador::create([
            'mir_nivel_id' => $nivelId,
            'nombre' => '',
            'tipo' => $reglas['tipo_default'],
            'dimension' => $reglas['dimensiones'][0],
            'frecuencia' => $reglas['frecuencias'][0],
            'orden' => $maxOrden + 1,
        ]);
    }

    public function guardarIndicador(int $indicadorId, array $data): void
    {
        $indicador = Indicador::findOrFail($indicadorId);
        $nivel = $indicador->mirNivel;
        $reglas = IndicadorReglasService::reglasParaNivel($nivel->tipo_nivel);

        $validated = validator($data, [
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:' . implode(',', $reglas['tipos']),
            'dimension' => 'required|in:' . implode(',', $reglas['dimensiones']),
            'frecuencia' => 'required|in:' . implode(',', $reglas['frecuencias']),
        ])->validate();

        $indicador->update($validated);
    }

    public function eliminarIndicador(int $indicadorId): void
    {
        Indicador::findOrFail($indicadorId)->delete();
    }

    public function agregarMedioVerificacion(int $indicadorId): void
    {
        $maxOrden = MedioVerificacion::where('indicador_id', $indicadorId)->max('orden') ?? 0;

        MedioVerificacion::create([
            'indicador_id' => $indicadorId,
            'nombre' => '',
            'orden' => $maxOrden + 1,
        ]);
    }

    public function guardarMedioVerificacion(int $medioId, string $nombre, ?string $fuente = null): void
    {
        MedioVerificacion::findOrFail($medioId)->update([
            'nombre' => $nombre,
            'fuente' => $fuente,
        ]);
    }

    public function eliminarMedioVerificacion(int $medioId): void
    {
        MedioVerificacion::findOrFail($medioId)->delete();
    }

    public function validarSintaxis(int $nivelId): void
    {
        $nivel = MirNivel::findOrFail($nivelId);

        if (empty($nivel->resumen_narrativo)) {
            return;
        }

        $promptView = 'prompts.mir.validar-sintaxis-' . $nivel->tipo_nivel->value;
        $promptText = view($promptView, ['texto' => $nivel->resumen_narrativo])->render();

        try {
            $llm = app(LlmServiceInterface::class);
            $result = $llm->validate($promptText, []);

            $nivel->update([
                'sintaxis_valida' => $result->isValid,
                'sintaxis_observacion' => implode('; ', $result->issues),
                'sintaxis_sugerencia' => $result->suggestion,
                'sintaxis_validada_at' => now(),
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo validar la sintaxis con IA.');
        }
    }

    public function validarCremaa(int $indicadorId): void
    {
        $indicador = Indicador::with('mirNivel')->findOrFail($indicadorId);

        if (empty($indicador->nombre)) {
            return;
        }

        $promptText = view('prompts.mir.validar-cremaa', [
            'nombre' => $indicador->nombre,
            'formula' => $indicador->formula_texto,
            'tipo' => $indicador->tipo?->label() ?? '',
            'dimension' => $indicador->dimension?->label() ?? '',
            'frecuencia' => $indicador->frecuencia?->label() ?? '',
            'resumenNarrativo' => $indicador->mirNivel->resumen_narrativo,
        ])->render();

        try {
            $llm = app(LlmServiceInterface::class);
            $result = $llm->suggest($promptText);
            $data = json_decode($result, true);

            if (!is_array($data)) {
                return;
            }

            $cremaaFields = ['claro', 'relevante', 'economico', 'monitoreable', 'adecuado', 'aportante'];
            $upsertData = ['indicador_id' => $indicadorId];

            foreach ($cremaaFields as $field) {
                $upsertData[$field] = (bool) ($data[$field] ?? false);
                $upsertData[$field . '_observacion'] = $data[$field . '_observacion'] ?? null;
            }

            CremaaValidacion::updateOrCreate(
                ['indicador_id' => $indicadorId],
                $upsertData
            );
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo validar CREMAA con IA.');
        }
    }

    public function buscarAlineacion(int $nivelId): void
    {
        $nivel = MirNivel::findOrFail($nivelId);

        if (empty($nivel->resumen_narrativo)) {
            return;
        }

        $this->nivelAlineacionActivo = $nivelId;

        try {
            $search = app(SemanticSearchService::class);

            // Fin/Propósito → Objetivos Estratégicos PED
            // Componente/Actividad → Líneas de Acción
            if (in_array($nivel->tipo_nivel, [TipoNivelMir::FIN, TipoNivelMir::PROPOSITO])) {
                $results = $search->findSimilar(
                    $nivel->resumen_narrativo,
                    \App\Models\PedObjetivoEstrategico::class,
                    5
                );
            } else {
                $results = $search->findSimilar(
                    $nivel->resumen_narrativo,
                    \App\Models\PedLineaAccion::class,
                    5
                );
            }

            $this->sugerenciasAlineacion = $results->map(fn ($r) => [
                'id' => $r->model->id,
                'tipo' => class_basename($r->model),
                'descripcion' => $r->model->descripcion ?? $r->model->nombre ?? '',
                'score' => round($r->score * 100, 1),
            ])->toArray();
        } catch (\Exception $e) {
            $this->sugerenciasAlineacion = [];
            session()->flash('error', 'No se pudo buscar alineación: ' . $e->getMessage());
        }
    }

    public function seleccionarAlineacion(int $nivelId, string $tipo, int $entidadId): void
    {
        $nivel = MirNivel::findOrFail($nivelId);

        $updateData = [];
        if ($tipo === 'PedObjetivoEstrategico') {
            $updateData['ped_objetivo_estrategico_id'] = $entidadId;
        } elseif ($tipo === 'PedLineaAccion') {
            $updateData['ped_linea_accion_id'] = $entidadId;
        }

        $nivel->update($updateData);
        $this->sugerenciasAlineacion = [];
        $this->nivelAlineacionActivo = null;
    }

    public function validarMirCompleta(): void
    {
        try {
            $servicio = app(MirLogicaValidacionService::class);
            $resultado = $servicio->validarCompleta($this->programa);
            $this->hallazgosLogica = $resultado['hallazgos'] ?? [];
            $this->validacionLogicaEjecutada = true;
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo ejecutar la validación lógica.');
        }
    }

    public function aceptarSugerencia(int $nivelId): void
    {
        $nivel = MirNivel::findOrFail($nivelId);

        if ($nivel->sintaxis_sugerencia) {
            $nivel->update([
                'resumen_narrativo' => $nivel->sintaxis_sugerencia,
                'sintaxis_valida' => null,
                'sintaxis_observacion' => null,
                'sintaxis_sugerencia' => null,
                'sintaxis_validada_at' => null,
            ]);
        }
    }

    public function render()
    {
        $fin = $this->programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::FIN->value)
            ->first();

        $proposito = $this->programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::PROPOSITO->value)
            ->first();

        $componentes = $this->programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE->value)
            ->with(['actividades.indicadores.mediosVerificacion', 'actividades.indicadores.cremaaValidacion', 'actividades.pedObjetivoEstrategico', 'actividades.pedLineaAccion', 'indicadores.mediosVerificacion', 'indicadores.cremaaValidacion', 'pedObjetivoEstrategico', 'pedLineaAccion'])
            ->orderBy('orden')
            ->get();

        // Load indicadores and alignment for fin and proposito
        $fin?->load(['indicadores.mediosVerificacion', 'indicadores.cremaaValidacion', 'pedObjetivoEstrategico', 'pedLineaAccion']);
        $proposito?->load(['indicadores.mediosVerificacion', 'indicadores.cremaaValidacion', 'pedObjetivoEstrategico', 'pedLineaAccion']);

        // Build rules map for each nivel type
        $reglasMap = [];
        foreach (TipoNivelMir::cases() as $tipo) {
            $reglasMap[$tipo->value] = IndicadorReglasService::reglasParaNivel($tipo);
        }

        return view('livewire.mml.mir-editor', [
            'fin' => $fin,
            'proposito' => $proposito,
            'componentes' => $componentes,
            'reglasMap' => $reglasMap,
        ]);
    }
}
