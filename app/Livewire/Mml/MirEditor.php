<?php

namespace App\Livewire\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\FrecuenciaMedicion;
use App\Enums\SentidoIndicador;
use App\Enums\TipoFuenteMv;
use App\Enums\TipoNivelMir;
use App\Models\CatalogoUnidadMedida;
use App\Models\Evaluation\AnexoTransversal;
use App\Models\Mml\CremaaValidacion;
use App\Models\Mml\CremaValidacionMv;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\Mml\MirSupuesto;
use App\Models\Mml\RevisionMeta;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Services\Embeddings\SemanticSearchService;
use App\Services\Mml\IndicadorReglasService;
use App\Services\Mml\MirLogicaValidacionService;
use App\Services\Mml\MirPrellenadoService;
use App\Services\Mml\MirSnapshotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Etapa 7 — Matriz de Indicadores para Resultados')]
class MirEditor extends Component
{
    public ProgramaPresupuestario $programa;

    public array $hallazgosLogica = [];

    public bool $validacionLogicaEjecutada = false;

    public array $sugerenciasAlineacion = [];

    public ?int $nivelAlineacionActivo = null;

    public string $snapshotEtiqueta = '';

    public bool $mostrarVersiones = false;

    public ?int $editandoNivelId = null;

    public ?int $viendoVersionId = null;

    public ?array $snapshotData = null;

    /** @var array<int, string> warning B3 keyed por indicador id */
    public array $metaWarnings = [];

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;

        // Prellenar desde EAP si no hay niveles
        (new MirPrellenadoService)->prellenar($programa);
    }

    // Helpers de scoping (defensa-en-profundidad). Decisión de seguridad:
    // Miss → no-op silencioso (no 404). El editor solo opera sobre el programa
    // montado; un id ajeno solo llega vía request crafteado, que tratamos como
    // inerte sin revelar existencia de recursos ajenos (evita enumeración).

    /**
     * Resuelve un MirNivel garantizando que pertenece al programa montado.
     * Defensa-en-profundidad contra requests Livewire crafteados con ids ajenos.
     */
    private function nivelDelPrograma(int $id): ?MirNivel
    {
        return MirNivel::where('programa_presupuestario_id', $this->programa->id)->find($id);
    }

    /**
     * Resuelve un Indicador garantizando que su nivel pertenece al programa montado.
     */
    private function indicadorDelPrograma(int $id): ?Indicador
    {
        return Indicador::whereHas(
            'mirNivel',
            fn ($q) => $q->where('programa_presupuestario_id', $this->programa->id)
        )->find($id);
    }

    /**
     * Resuelve una IndicadorVariable garantizando que cuelga del programa montado.
     */
    private function variableDelPrograma(int $id): ?IndicadorVariable
    {
        return IndicadorVariable::whereHas(
            'indicador.mirNivel',
            fn ($q) => $q->where('programa_presupuestario_id', $this->programa->id)
        )->find($id);
    }

    /**
     * Resuelve un MedioVerificacion garantizando que cuelga del programa montado.
     */
    private function medioDelPrograma(int $id): ?MedioVerificacion
    {
        return MedioVerificacion::whereHas(
            'indicador.mirNivel',
            fn ($q) => $q->where('programa_presupuestario_id', $this->programa->id)
        )->find($id);
    }

    public function guardarNivel(int $nivelId, string $campo, string $valor): void
    {
        $nivel = $this->nivelDelPrograma($nivelId);

        if ($nivel === null) {
            return;
        }

        // 'supuestos' legacy ya no es escribible: los supuestos viven en
        // mir_supuestos (V2-A8) vía agregar/guardar/eliminarSupuesto.
        if (in_array($campo, ['resumen_narrativo'])) {
            $nivel->update([$campo => $valor]);
        }
    }

    /**
     * Resuelve un MirSupuesto garantizando que cuelga del programa montado.
     */
    private function supuestoDelPrograma(int $id): ?MirSupuesto
    {
        return MirSupuesto::whereHas(
            'mirNivel',
            fn ($q) => $q->where('programa_presupuestario_id', $this->programa->id)
        )->find($id);
    }

    public function agregarSupuesto(int $nivelId): void
    {
        $nivel = $this->nivelDelPrograma($nivelId);

        if ($nivel === null) {
            return;
        }

        $maxOrden = $nivel->supuestosEstructurados()->max('orden') ?? 0;

        MirSupuesto::create([
            'mir_nivel_id' => $nivelId,
            'descripcion' => '',
            'orden' => $maxOrden + 1,
        ]);
    }

    public function guardarSupuesto(int $supuestoId, array $data): void
    {
        $supuesto = $this->supuestoDelPrograma($supuestoId);

        if ($supuesto === null) {
            return;
        }

        $validated = validator($data, [
            'descripcion' => 'required|string|max:2000',
            'es_externo' => 'boolean',
            'es_relevante' => 'boolean',
            'probabilidad_razonable' => 'boolean',
        ])->validate();

        $supuesto->update($validated);
    }

    public function eliminarSupuesto(int $supuestoId): void
    {
        $supuesto = $this->supuestoDelPrograma($supuestoId);

        if ($supuesto === null) {
            return;
        }

        $supuesto->delete();
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
        if ($this->nivelDelPrograma($componenteId) === null) {
            return;
        }

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
        $nivel = $this->nivelDelPrograma($nivelId);

        if ($nivel === null) {
            return;
        }

        // Only allow deleting Componente/Actividad (not Fin/Propósito)
        if (in_array($nivel->tipo_nivel, [TipoNivelMir::COMPONENTE, TipoNivelMir::ACTIVIDAD])) {
            $nivel->delete();
        }
    }

    public function agregarIndicador(int $nivelId): void
    {
        $nivel = $this->nivelDelPrograma($nivelId);

        if ($nivel === null) {
            return;
        }

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
        $indicador = $this->indicadorDelPrograma($indicadorId);

        if ($indicador === null) {
            return;
        }

        $nivel = $indicador->mirNivel;
        $reglas = IndicadorReglasService::reglasParaNivel($nivel->tipo_nivel);

        $validated = validator($data, [
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:'.implode(',', $reglas['tipos']),
            'dimension' => 'required|in:'.implode(',', $reglas['dimensiones']),
            'frecuencia' => 'required|in:'.implode(',', $reglas['frecuencias']),
            'sentido' => ['required', Rule::in(SentidoIndicador::values())],
        ])->validate();

        $indicador->update($validated);
    }

    public function syncAnexosTransversales(int $indicadorId, array $anexoIds): void
    {
        $indicador = $this->indicadorDelPrograma($indicadorId);

        if ($indicador === null) {
            return;
        }

        $indicador->anexosTransversales()->sync(array_map('intval', $anexoIds));
    }

    public function eliminarIndicador(int $indicadorId): void
    {
        $indicador = $this->indicadorDelPrograma($indicadorId);

        if ($indicador === null) {
            return;
        }

        $indicador->delete();
    }

    public function agregarMedioVerificacion(int $indicadorId): void
    {
        if ($this->indicadorDelPrograma($indicadorId) === null) {
            return;
        }

        $maxOrden = MedioVerificacion::where('indicador_id', $indicadorId)->max('orden') ?? 0;

        MedioVerificacion::create([
            'indicador_id' => $indicadorId,
            'nombre' => '',
            'orden' => $maxOrden + 1,
        ]);
    }

    public function guardarMedioVerificacion(int $medioId, array $data): void
    {
        $medio = $this->medioDelPrograma($medioId);

        if ($medio === null) {
            return;
        }

        $medio->load('indicador.mirNivel');

        $validated = validator($data, [
            'nombre' => 'required|string|max:255',
            'fuente' => 'nullable|string|max:255',
            'tipo_fuente' => ['nullable', Rule::in(TipoFuenteMv::values())],
            'organismo' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:2048',
            'frecuencia' => ['nullable', Rule::in(FrecuenciaMedicion::values())],
        ])->validate();

        // Validación B9 (C-073): FIN/PROPÓSITO exigen fuente externa. NULL no
        // bloquea (legacy sin clasificar; el diagnóstico lo reporta aparte).
        $errorB9 = IndicadorReglasService::validarTipoFuenteMv(
            $medio->indicador->mirNivel->tipo_nivel,
            $validated['tipo_fuente'] ?? null,
        );

        if ($errorB9 !== null) {
            throw ValidationException::withMessages([
                "tipo_fuente_mv_{$medioId}" => $errorB9,
            ]);
        }

        // Validación cruzada B7: el MV debe publicarse al menos tan seguido como
        // se mide el indicador. Solo aplica cuando el valor entrante es un value
        // del enum (frecuencias legacy no-enum en BD no bloquean otros guardados).
        $frecuenciaMv = $validated['frecuencia'] ?? null;
        $indicador = $medio->indicador;

        if ($frecuenciaMv !== null && $indicador !== null && $indicador->frecuencia !== null) {
            $ordenMv = FrecuenciaMedicion::from($frecuenciaMv)->orden();
            $ordenIndicador = $indicador->frecuencia->orden();

            if ($ordenMv > $ordenIndicador) {
                throw ValidationException::withMessages([
                    "frecuencia_mv_{$medioId}" => sprintf(
                        'El medio de verificación debe publicarse al menos con la misma frecuencia con la que se mide el indicador (indicador: %s, MV: %s).',
                        $indicador->frecuencia->label(),
                        FrecuenciaMedicion::from($frecuenciaMv)->label(),
                    ),
                ]);
            }
        }

        $medio->update($validated);
    }

    public function eliminarMedioVerificacion(int $medioId): void
    {
        $medio = $this->medioDelPrograma($medioId);

        if ($medio === null) {
            return;
        }

        $medio->delete();
    }

    private const CREMA_MV_FIELDS = ['confiable', 'relevante', 'economico', 'monitoreable', 'asequible'];

    /**
     * Checklist CREMA del MV capturada a mano (C-072): Confiable, Relevante,
     * Económico, Monitoreable, Asequible.
     */
    public function guardarCremaMv(int $medioId, array $data): void
    {
        $medio = $this->medioDelPrograma($medioId);

        if ($medio === null) {
            return;
        }

        $rules = [];
        foreach (self::CREMA_MV_FIELDS as $field) {
            $rules[$field] = 'boolean';
            $rules[$field.'_observacion'] = 'nullable|string|max:2000';
        }

        $validated = validator($data, $rules)->validate();

        CremaValidacionMv::updateOrCreate(
            ['medio_verificacion_id' => $medioId],
            $validated,
        );
    }

    /**
     * Evalúa la checklist CREMA del MV con IA (mismo pipeline LLM que la
     * CREMAA del indicador).
     */
    public function validarCremaMv(int $medioId): void
    {
        $medio = $this->medioDelPrograma($medioId);

        if ($medio === null) {
            return;
        }

        $medio->load('indicador.mirNivel');

        if (empty($medio->nombre)) {
            return;
        }

        $promptText = view('prompts.mir.validar-crema-mv', [
            'nombre' => $medio->nombre,
            'fuente' => $medio->fuente,
            'tipoFuente' => $medio->tipo_fuente ? TipoFuenteMv::tryFrom($medio->tipo_fuente)?->label() : null,
            'organismo' => $medio->organismo,
            'url' => $medio->url,
            'frecuencia' => $medio->frecuencia,
            'indicador' => $medio->indicador?->nombre,
            'resumenNarrativo' => $medio->indicador?->mirNivel?->resumen_narrativo,
        ])->render();

        try {
            $llm = app(LlmServiceInterface::class);
            $result = $llm->suggest($promptText);
            $data = json_decode($result, true);

            if (! is_array($data)) {
                return;
            }

            $upsertData = ['medio_verificacion_id' => $medioId];

            foreach (self::CREMA_MV_FIELDS as $field) {
                $upsertData[$field] = (bool) ($data[$field] ?? false);
                $upsertData[$field.'_observacion'] = $data[$field.'_observacion'] ?? null;
            }

            CremaValidacionMv::updateOrCreate(
                ['medio_verificacion_id' => $medioId],
                $upsertData,
            );
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo validar CREMA del MV con IA.');
        }
    }

    public function extraerVariables(int $indicadorId): void
    {
        $indicador = $this->indicadorDelPrograma($indicadorId);

        if ($indicador === null) {
            return;
        }

        if (empty($indicador->formula_texto)) {
            return;
        }

        $promptText = view('prompts.mir.extraer-variables', [
            'formula' => $indicador->formula_texto,
        ])->render();

        try {
            $llm = app(LlmServiceInterface::class);
            $result = $llm->suggest($promptText);
            $data = json_decode($result, true);

            if (! is_array($data)) {
                return;
            }

            // Clear existing variables and recreate
            $indicador->variables()->delete();

            foreach ($data as $i => $var) {
                IndicadorVariable::create([
                    'indicador_id' => $indicadorId,
                    'simbolo' => $var['simbolo'] ?? chr(65 + $i),
                    'nombre' => $var['nombre'] ?? '',
                    'descripcion' => $var['descripcion'] ?? null,
                    'orden' => $i + 1,
                ]);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudieron extraer las variables con IA.');
        }
    }

    public function agregarVariable(int $indicadorId): void
    {
        if ($this->indicadorDelPrograma($indicadorId) === null) {
            return;
        }

        $maxOrden = IndicadorVariable::where('indicador_id', $indicadorId)->max('orden') ?? 0;
        $nextSymbol = chr(65 + $maxOrden); // A, B, C...

        IndicadorVariable::create([
            'indicador_id' => $indicadorId,
            'simbolo' => $nextSymbol,
            'nombre' => '',
            'orden' => $maxOrden + 1,
        ]);
    }

    public function guardarVariable(int $variableId, array $data): void
    {
        $variable = $this->variableDelPrograma($variableId);

        if ($variable === null) {
            return;
        }

        $validated = validator($data, [
            'simbolo' => 'required|string|max:5',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'fuente' => 'nullable|string|max:255',
            'unidad_medida_id' => 'nullable|integer|exists:catalogo_unidades_medida,id',
        ])->validate();

        $variable->update($validated);
    }

    public function eliminarVariable(int $variableId): void
    {
        $variable = $this->variableDelPrograma($variableId);

        if ($variable === null) {
            return;
        }

        $variable->delete();
    }

    public function guardarFormulaTexto(int $indicadorId, string $formula): void
    {
        $indicador = $this->indicadorDelPrograma($indicadorId);

        if ($indicador === null) {
            return;
        }

        $indicador->update(['formula_texto' => $formula]);
    }

    public function guardarLineaBaseAnio(int $indicadorId, ?string $anio): void
    {
        $indicador = $this->indicadorDelPrograma($indicadorId);

        if ($indicador === null) {
            return;
        }

        $validated = validator(
            ['linea_base_anio' => $anio === '' ? null : $anio],
            ['linea_base_anio' => 'nullable|integer|between:1900,2999']
        )->validate();

        $indicador->update($validated);
    }

    public function guardarLineaBase(int $indicadorId, ?string $valor): void
    {
        $indicador = $this->indicadorDelPrograma($indicadorId);

        if ($indicador === null) {
            return;
        }

        $validated = validator(
            ['linea_base' => $valor === '' ? null : $valor],
            ['linea_base' => 'nullable|numeric']
        )->validate();

        $indicador->update($validated);
    }

    /**
     * Captura/edición de la meta anual del indicador con justificación
     * obligatoria al modificarla (C-146). Regla del temario: "no bajar meta /
     * no cambiar sin justificación". La primera definición (null → valor) y el
     * reenvío del mismo valor NO exigen justificación ni generan audit trail.
     */
    public function guardarMeta(int $indicadorId, $meta, ?string $justificacion = null): void
    {
        $clave = "meta_{$indicadorId}";

        // Limpia cualquier warning stale de este indicador en TODOS los paths
        // (scoping, no-numérico, sin justificación, éxito).
        unset($this->metaWarnings[$indicadorId]);

        // Scoping: el indicador debe pertenecer al programa del editor.
        $indicador = $this->indicadorDelPrograma($indicadorId);

        if ($indicador === null) {
            return;
        }

        // Normalizar meta: '' → null, numérico → float, no numérico → error.
        if ($meta === null || $meta === '') {
            $metaNueva = null;
        } elseif (! is_numeric($meta)) {
            $this->addError($clave, 'La meta debe ser un valor numérico.');

            return;
        } else {
            $metaNueva = (float) $meta;
        }

        $metaPrevia = $indicador->meta === null ? null : (float) $indicador->meta;

        $hayCambio = $metaPrevia !== null
            && $metaNueva !== null
            && $this->valorCambio($metaPrevia, $metaNueva);

        // Cambio sobre meta ya definida → justificación obligatoria.
        if ($hayCambio) {
            $justificacion = $justificacion === null ? '' : trim($justificacion);

            if (mb_strlen($justificacion) < 10) {
                $this->addError($clave, 'Modificar la meta requiere una justificación de al menos 10 caracteres.');

                return;
            }
        }

        DB::transaction(function () use ($indicador, $metaNueva, $metaPrevia, $hayCambio, $justificacion) {
            $indicador->update(['meta' => $metaNueva]);

            if ($hayCambio) {
                RevisionMeta::create([
                    'indicador_id' => $indicador->id,
                    'valor_anterior' => $metaPrevia,
                    'valor_nuevo' => $metaNueva,
                    'justificacion' => $justificacion,
                    'user_id' => auth()->id(),
                ]);
            }
        });

        $this->resetErrorBag($clave);

        // Advertencia B3 no bloqueante: la meta queda fuera del rango verde.
        if ($metaNueva !== null
            && $indicador->rango_verde_min !== null
            && $indicador->rango_verde_max !== null) {
            $min = (float) $indicador->rango_verde_min;
            $max = (float) $indicador->rango_verde_max;

            if ($metaNueva < $min || $metaNueva > $max) {
                $this->metaWarnings[$indicadorId] = "La meta queda fuera del rango verde [{$min}, {$max}].";
            }
        }
    }

    /**
     * Compara dos valores de meta con la precisión decimal de BD (4 decimales),
     * mismo criterio que CalendarizacionService::valorCambio.
     */
    private function valorCambio(float $anterior, float $nuevo): bool
    {
        return number_format($anterior, 4, '.', '')
            !== number_format($nuevo, 4, '.', '');
    }

    public function guardarSemaforo(int $indicadorId, array $rangos): void
    {
        $indicador = $this->indicadorDelPrograma($indicadorId);

        if ($indicador === null) {
            return;
        }

        $indicador->load('unidadMedida');
        $clave = 'semaforo_'.$indicadorId;

        $campos = [
            'rango_verde_min', 'rango_verde_max',
            'rango_amarillo_min', 'rango_amarillo_max',
            'rango_rojo_min', 'rango_rojo_max',
            'rango_rojo_alto_min', 'rango_rojo_alto_max',
        ];

        // 1. Normalizar: '' → null, numérico → float, no-numérico → error de validación.
        $normalizados = [];
        foreach ($campos as $campo) {
            $valor = $rangos[$campo] ?? null;

            if ($valor === null || $valor === '') {
                $normalizados[$campo] = null;

                continue;
            }

            if (! is_numeric($valor)) {
                $this->addError($clave, 'Los rangos del semáforo deben ser valores numéricos.');

                return;
            }

            $normalizados[$campo] = (float) $valor;
        }

        // 2. Validaciones duras de negocio (Task 2).
        $errores = IndicadorReglasService::validarRangosSemaforo($indicador, $normalizados);

        // 3. Si hay errores, no guarda.
        if (! empty($errores)) {
            $this->addError($clave, implode(' ', $errores));

            return;
        }

        // 4. OK → persiste y limpia el error.
        $indicador->update($normalizados);
        $this->resetErrorBag($clave);
    }

    public function sugerirFormula(int $indicadorId): void
    {
        $indicador = $this->indicadorDelPrograma($indicadorId);

        if ($indicador === null) {
            return;
        }

        $indicador->load('mirNivel');
        $nivel = $indicador->mirNivel;

        $llm = app(LlmServiceInterface::class);
        $prompt = "Para el indicador \"{$indicador->nombre}\" "
            ."(tipo: {$indicador->tipo->value}, dimensión: {$indicador->dimension->value}) "
            ."del nivel MIR \"{$nivel->tipo_nivel->label()}: {$nivel->resumen_narrativo}\", "
            .'sugiere una fórmula de cálculo clara y precisa. '
            .'La fórmula debe usar nombres de variables descriptivos. '
            .'Responde SOLO con la fórmula, sin explicaciones.';

        try {
            $formula = $llm->suggest($prompt);
            $indicador->update(['formula_texto' => trim($formula)]);
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'No se pudo generar la fórmula.');
        }
    }

    public function validarSintaxis(int $nivelId): void
    {
        $nivel = $this->nivelDelPrograma($nivelId);

        if ($nivel === null) {
            return;
        }

        if (empty($nivel->resumen_narrativo)) {
            return;
        }

        $promptView = 'prompts.mir.validar-sintaxis-'.$nivel->tipo_nivel->value;
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
        $indicador = $this->indicadorDelPrograma($indicadorId);

        if ($indicador === null) {
            return;
        }

        $indicador->load('mirNivel');

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

            if (! is_array($data)) {
                return;
            }

            $cremaaFields = ['claro', 'relevante', 'economico', 'monitoreable', 'adecuado', 'aportante'];
            $upsertData = ['indicador_id' => $indicadorId];

            foreach ($cremaaFields as $field) {
                $upsertData[$field] = (bool) ($data[$field] ?? false);
                $upsertData[$field.'_observacion'] = $data[$field.'_observacion'] ?? null;
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
        $nivel = $this->nivelDelPrograma($nivelId);

        if ($nivel === null) {
            return;
        }

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

            $this->sugerenciasAlineacion = $results->map(fn ($r) => [
                'id' => $r->model->id,
                'tipo' => class_basename($r->model),
                'descripcion' => $r->model->descripcion ?? $r->model->nombre ?? '',
                'score' => round($r->score * 100, 1),
            ])->toArray();
        } catch (\Exception $e) {
            $this->sugerenciasAlineacion = [];
            session()->flash('error', 'No se pudo buscar alineación: '.$e->getMessage());
        }
    }

    public function seleccionarAlineacion(int $nivelId, string $tipo, int $entidadId): void
    {
        $nivel = $this->nivelDelPrograma($nivelId);

        if ($nivel === null) {
            return;
        }

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
        $nivel = $this->nivelDelPrograma($nivelId);

        if ($nivel === null) {
            return;
        }

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

    public function asignarUrCoadyuvante(int $nivelId, ?int $teamId): void
    {
        $nivel = $this->nivelDelPrograma($nivelId);

        if ($nivel === null) {
            return;
        }

        if (! in_array($nivel->tipo_nivel, [TipoNivelMir::COMPONENTE, TipoNivelMir::ACTIVIDAD])) {
            return;
        }

        $oldTeamId = $nivel->team_id;
        $nivel->update(['team_id' => $teamId ?: null]);

        if ($teamId) {
            $this->programa->equipos()->syncWithoutDetaching([
                $teamId => ['rol' => 'coadyuvante'],
            ]);
        }

        // If old UR was removed, check if it still has other niveles
        if ($oldTeamId && $oldTeamId !== $teamId) {
            $otrosNiveles = MirNivel::where('programa_presupuestario_id', $this->programa->id)
                ->where('team_id', $oldTeamId)
                ->exists();

            if (! $otrosNiveles) {
                $this->programa->equipos()
                    ->wherePivot('rol', 'coadyuvante')
                    ->detach($oldTeamId);
            }
        }
    }

    public function crearSnapshot(): void
    {
        if (empty($this->snapshotEtiqueta)) {
            return;
        }

        $service = app(MirSnapshotService::class);
        $service->crear($this->programa, $this->snapshotEtiqueta, auth()->id());

        $this->snapshotEtiqueta = '';
        session()->flash('success', 'Snapshot creado correctamente.');
    }

    public function restaurarVersion(int $versionId): void
    {
        $version = $this->programa->mirVersiones()->findOrFail($versionId);

        $service = app(MirSnapshotService::class);
        $service->restaurar($version);

        session()->flash('success', 'MIR restaurada desde snapshot.');
    }

    public function toggleEditarNivel(?int $nivelId): void
    {
        $this->editandoNivelId = $this->editandoNivelId === $nivelId ? null : $nivelId;
    }

    public function cargarVersion(?int $versionId): void
    {
        if (! $versionId) {
            $this->viendoVersionId = null;
            $this->snapshotData = null;

            return;
        }
        $version = $this->programa->mirVersiones()->findOrFail($versionId);
        $this->viendoVersionId = $versionId;
        $this->snapshotData = $version->snapshot;
    }

    public function volverAVersionActual(): void
    {
        $this->viendoVersionId = null;
        $this->snapshotData = null;
    }

    public function toggleVersiones(): void
    {
        $this->mostrarVersiones = ! $this->mostrarVersiones;
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
            ->with(['actividades.indicadores.mediosVerificacion.cremaValidacion', 'actividades.indicadores.cremaaValidacion', 'actividades.indicadores.variables', 'actividades.indicadores.anexosTransversales', 'actividades.pedObjetivoEstrategico', 'actividades.pedLineaAccion', 'actividades.team', 'actividades.supuestosEstructurados', 'indicadores.mediosVerificacion.cremaValidacion', 'indicadores.cremaaValidacion', 'indicadores.variables', 'indicadores.anexosTransversales', 'pedObjetivoEstrategico', 'pedLineaAccion', 'team', 'supuestosEstructurados'])
            ->orderBy('orden')
            ->get();

        // Load indicadores and alignment for fin and proposito
        $fin?->load(['indicadores.mediosVerificacion', 'indicadores.cremaaValidacion', 'indicadores.variables', 'indicadores.anexosTransversales', 'pedObjetivoEstrategico', 'pedLineaAccion']);
        $proposito?->load(['indicadores.mediosVerificacion', 'indicadores.cremaaValidacion', 'indicadores.variables', 'indicadores.anexosTransversales', 'pedObjetivoEstrategico', 'pedLineaAccion']);

        // Build rules map for each nivel type
        $reglasMap = [];
        foreach (TipoNivelMir::cases() as $tipo) {
            $reglasMap[$tipo->value] = IndicadorReglasService::reglasParaNivel($tipo);
        }

        $unidadesMedida = CatalogoUnidadMedida::orderBy('nombre')->get();

        $versiones = $this->programa->mirVersiones()->with('creador')->latest()->get();

        $teams = Team::orderBy('name')->get();
        $anexosTransversales = AnexoTransversal::activos()->get();

        $nivelesSinEficacia = IndicadorReglasService::nivelesSinEficacia($this->programa);

        return view('livewire.mml.mir-editor', [
            'fin' => $fin,
            'proposito' => $proposito,
            'componentes' => $componentes,
            'reglasMap' => $reglasMap,
            'unidadesMedida' => $unidadesMedida,
            'versiones' => $versiones,
            'teams' => $teams,
            'anexosTransversales' => $anexosTransversales,
            'nivelesSinEficacia' => $nivelesSinEficacia,
        ]);
    }
}
