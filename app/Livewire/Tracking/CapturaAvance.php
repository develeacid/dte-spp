<?php

namespace App\Livewire\Tracking;

use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceVariable;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use App\Services\Tracking\FormulaEvaluatorService;
use App\Services\Tracking\JustificacionService;
use App\Services\Tracking\SemaforoService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CapturaAvance extends Component
{
    public Avance $avance;

    /** @var array<int, float|null> keyed by indicador_variable_id */
    public array $valores = [];

    public ?float $resultado = null;

    public ?string $semaforoCalculado = null;

    public ?string $justificacion = null;

    public ?string $justificacionIa = null;

    public function mount(Avance $avance): void
    {
        $avance->load(['indicador.variables', 'indicador.mirNivel', 'metaPeriodo', 'variables']);

        if (! $avance->estado->esEditable() && ! $avance->estaCongelado()) {
            // Allow viewing but form will be disabled
        }

        $this->avance = $avance;
        $this->justificacion = $avance->justificacion_final;
        $this->justificacionIa = $avance->justificacion_ia;
        $this->resultado = $avance->resultado ? (float) $avance->resultado : null;
        $this->semaforoCalculado = $avance->semaforo_calculado;

        // Load existing variable values
        foreach ($avance->indicador->variables as $variable) {
            $existente = $avance->variables->firstWhere('indicador_variable_id', $variable->id);
            $this->valores[$variable->id] = $existente ? (float) $existente->valor : null;
        }
    }

    public function calcular(): void
    {
        $indicador = $this->avance->indicador;

        if (! $indicador->formula_texto) {
            return;
        }

        // Build variables array: symbol => value
        $variablesMap = [];
        foreach ($indicador->variables as $variable) {
            $valor = $this->valores[$variable->id] ?? null;
            if ($valor === null || $valor === '') {
                return; // Cannot calculate without all variables
            }
            $variablesMap[$variable->simbolo] = (float) $valor;
        }

        $evaluator = new FormulaEvaluatorService();
        $resultado = $evaluator->evaluar($indicador->formula_texto, $variablesMap);

        if ($resultado === null) {
            $this->resultado = null;
            $this->semaforoCalculado = null;

            return;
        }

        $this->resultado = $resultado;

        $semaforoService = new SemaforoService();
        $metaPeriodo = $this->avance->metaPeriodo
            ? (float) $this->avance->metaPeriodo->meta_periodo
            : null;

        $this->semaforoCalculado = $semaforoService->calcular($resultado, $indicador, $metaPeriodo);

        // Auto-generate AI justification when semaforo is amarillo or rojo
        if (in_array($this->semaforoCalculado, ['amarillo', 'rojo']) && ! $this->justificacionIa) {
            $this->generarJustificacionIa();
        }
    }

    public function generarJustificacionIa(): void
    {
        if ($this->resultado === null) {
            return;
        }

        // Temporarily set resultado on avance for the service
        $this->avance->resultado = $this->resultado;
        $this->avance->semaforo_calculado = $this->semaforoCalculado;

        $service = app(JustificacionService::class);
        $draft = $service->generar($this->avance);

        if ($draft) {
            $this->justificacionIa = $draft;

            // Pre-fill justificacion if user hasn't written one yet
            if (empty($this->justificacion)) {
                $this->justificacion = $draft;
            }
        }
    }

    public function sincronizarVariable(int $variableId): void
    {
        $variable = $this->avance->indicador->variables->firstWhere('id', $variableId);

        if (! $variable || ! $variable->hasGeoBaseLink()) {
            return;
        }

        try {
            $client = app(GeoBaseClient::class);

            $response = match ($variable->geobase_endpoint_type) {
                'component_coverage' => $client->getComponentCoverage($variable->spp_reference_id),
                'program_coverage' => $client->getProgramCoverage($variable->spp_reference_id),
                default => null,
            };

            if ($response && isset($response[$variable->geobase_value_key ?? 'count'])) {
                $value = $response[$variable->geobase_value_key ?? 'count'];
                $this->valores[$variableId] = $value;

                AvanceVariable::updateOrCreate(
                    [
                        'avance_id' => $this->avance->id,
                        'indicador_variable_id' => $variableId,
                    ],
                    [
                        'valor' => $value,
                        'synced_from_geobase' => true,
                        'synced_at' => now(),
                    ],
                );

                $this->avance->load('variables');
                $this->calcular();
                session()->flash('sync_success', "Variable '{$variable->nombre}' sincronizada desde GeoBase.");
            }
        } catch (GeoBaseException $e) {
            session()->flash('sync_error', "No se pudo conectar con GeoBase: {$e->getMessage()}. Capture el valor manualmente.");
        }
    }

    public function guardar(): void
    {
        $rules = [];
        foreach ($this->avance->indicador->variables as $variable) {
            $rules["valores.{$variable->id}"] = 'required|numeric';
        }

        if (in_array($this->semaforoCalculado, ['amarillo', 'rojo'])) {
            $rules['justificacion'] = 'required|string|min:10';
        }

        $this->validate($rules, [
            'valores.*.required' => 'Este campo es obligatorio.',
            'valores.*.numeric' => 'Debe ser un valor numerico.',
            'justificacion.required' => 'La justificacion es obligatoria cuando el semaforo es amarillo o rojo.',
            'justificacion.min' => 'La justificacion debe tener al menos 10 caracteres.',
        ]);

        if ($this->avance->estaCongelado()) {
            abort(403, 'El avance esta congelado.');
        }

        if (! $this->avance->estado->esEditable()) {
            abort(403, 'El avance no es editable.');
        }

        // Save variable values
        foreach ($this->avance->indicador->variables as $variable) {
            AvanceVariable::updateOrCreate(
                [
                    'avance_id' => $this->avance->id,
                    'indicador_variable_id' => $variable->id,
                ],
                [
                    'valor' => $this->valores[$variable->id],
                ]
            );
        }

        // Update avance
        $this->avance->update([
            'resultado' => $this->resultado,
            'semaforo_calculado' => $this->semaforoCalculado,
            'justificacion_ia' => $this->justificacionIa,
            'justificacion_final' => $this->justificacion,
        ]);

        session()->flash('message', 'Avance guardado correctamente.');
    }

    public function render()
    {
        return view('livewire.tracking.captura-avance');
    }
}
