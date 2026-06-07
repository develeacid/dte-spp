<?php

namespace App\Livewire\Evaluation;

use App\Enums\PrioridadRecomendacion;
use App\Enums\SeveridadHallazgo;
use App\Models\Evaluation\EvaluacionExterna;
use App\Models\Evaluation\Hallazgo;
use App\Models\Evaluation\InformeEvaluacion;
use App\Models\Evaluation\Recomendacion;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class InformeEvaluacionEditor extends Component
{
    public EvaluacionExterna $evaluacionExterna;

    public InformeEvaluacion $informe;

    /** Campos de texto editables por sección (whitelist). */
    private const CAMPOS_SECCION = [
        'resumen_ejecutivo',
        'metodologia',
        'conclusiones',
        'fichas',
    ];

    /** Estado editable de las 4 secciones de texto, indexado por nombre de campo. */
    public array $secciones = [
        'resumen_ejecutivo' => '',
        'metodologia' => '',
        'conclusiones' => '',
        'fichas' => '',
    ];

    public array $nuevoHallazgo = [
        'descripcion' => '',
        'severidad' => 'media',
        'evidencia_url' => '',
    ];

    /** Estado de los forms inline de recomendación, indexado por hallazgo_id. */
    public array $nuevaRecomendacion = [];

    public function mount(EvaluacionExterna $evaluacionExterna): void
    {
        $this->evaluacionExterna = $evaluacionExterna;

        // El informe nace 1:1 al crear la evaluación (Task 3), pero las
        // evaluaciones creadas por factory en tests no traen informe.
        // firstOrCreate garantiza que siempre exista uno scopeado a ESTA evaluación.
        $this->informe = InformeEvaluacion::firstOrCreate(
            ['evaluacion_externa_id' => $evaluacionExterna->id],
        );

        foreach (self::CAMPOS_SECCION as $campo) {
            $this->secciones[$campo] = (string) ($this->informe->{$campo} ?? '');
        }
    }

    private function autorizarGestion(): void
    {
        abort_unless(auth()->user()->can('gestionar_evaluacion_externa'), 403);
    }

    public function guardarSeccion(string $campo, ?string $valor): void
    {
        $this->autorizarGestion();

        if (! in_array($campo, self::CAMPOS_SECCION, true)) {
            return;
        }

        $this->informe->update([$campo => $valor]);

        session()->flash('status', 'Sección guardada.');
    }

    public function agregarHallazgo(): void
    {
        $this->autorizarGestion();

        $validado = $this->validate([
            'nuevoHallazgo.descripcion' => ['required', 'string', 'min:10'],
            'nuevoHallazgo.severidad' => ['required', Rule::in(SeveridadHallazgo::values())],
            'nuevoHallazgo.evidencia_url' => ['nullable', 'url', 'max:2048'],
        ])['nuevoHallazgo'];

        $this->informe->hallazgos()->create([
            'descripcion' => $validado['descripcion'],
            'severidad' => $validado['severidad'],
            'evidencia_url' => $validado['evidencia_url'] ?: null,
        ]);

        $this->nuevoHallazgo = [
            'descripcion' => '',
            'severidad' => 'media',
            'evidencia_url' => '',
        ];

        session()->flash('status', 'Hallazgo agregado.');
    }

    public function eliminarHallazgo(int $id): void
    {
        $this->autorizarGestion();

        // Scoping: solo hallazgos del informe de ESTA evaluación.
        $hallazgo = $this->informe->hallazgos()->find($id);

        if (! $hallazgo) {
            return;
        }

        $hallazgo->recomendaciones()->delete();
        $hallazgo->delete();

        session()->flash('status', 'Hallazgo eliminado.');
    }

    public function agregarRecomendacion(int $hallazgoId): void
    {
        $this->autorizarGestion();

        // Scoping: el hallazgo debe pertenecer al informe de ESTA evaluación.
        $hallazgo = $this->informe->hallazgos()->find($hallazgoId);

        if (! $hallazgo) {
            return;
        }

        $validado = $this->validate([
            "nuevaRecomendacion.{$hallazgoId}.descripcion" => ['required', 'string', 'min:10'],
            "nuevaRecomendacion.{$hallazgoId}.prioridad" => ['required', Rule::in(PrioridadRecomendacion::values())],
        ])['nuevaRecomendacion'][$hallazgoId];

        $hallazgo->recomendaciones()->create([
            'descripcion' => $validado['descripcion'],
            'prioridad' => $validado['prioridad'],
        ]);

        unset($this->nuevaRecomendacion[$hallazgoId]);

        session()->flash('status', 'Recomendación agregada.');
    }

    public function eliminarRecomendacion(int $id): void
    {
        $this->autorizarGestion();

        // Scoping: la recomendación debe colgar de un hallazgo del informe de ESTA evaluación.
        $recomendacion = Recomendacion::query()
            ->whereHas('hallazgo', fn ($q) => $q->where('informe_evaluacion_id', $this->informe->id))
            ->find($id);

        if (! $recomendacion) {
            return;
        }

        $recomendacion->delete();

        session()->flash('status', 'Recomendación eliminada.');
    }

    public function render()
    {
        $hallazgos = $this->informe->hallazgos()
            ->with(['recomendaciones' => fn ($q) => $q->withCount('asms')])
            ->orderBy('id')
            ->get();

        $totalRecomendaciones = $hallazgos->sum(fn (Hallazgo $h) => $h->recomendaciones->count());

        return view('livewire.evaluation.externa.informe-editor', [
            'hallazgos' => $hallazgos,
            'totalRecomendaciones' => $totalRecomendaciones,
            'severidades' => SeveridadHallazgo::cases(),
            'prioridades' => PrioridadRecomendacion::cases(),
            'puedeGestionar' => auth()->user()->can('gestionar_evaluacion_externa'),
        ]);
    }
}
