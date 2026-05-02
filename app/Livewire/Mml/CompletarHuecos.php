<?php

namespace App\Livewire\Mml;

use App\DTOs\ImportedMirData;
use App\Enums\TipoNivelMir;
use App\Models\Mml\ImportacionReporte;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Services\Mml\MirPersistenciaService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Completar Huecos — MIR Importada')]
class CompletarHuecos extends Component
{
    public ImportacionReporte $reporte;

    public int $programaId;

    /** @var array Niveles with their indicators for the view */
    public array $niveles = [];

    /**
     * Critical fields that determine activo_seguimiento status.
     */
    private const CRITICAL_FIELDS = ['formula_texto', 'tipo', 'dimension', 'frecuencia'];

    public function mount(ImportacionReporte $importacion): void
    {
        $this->reporte = $importacion;

        // If not yet persisted, persist now
        if (! $this->reporte->programa_presupuestario_id) {
            $data = ImportedMirData::fromArray($this->reporte->datos_parseados);

            $programa = app(MirPersistenciaService::class)->persistir(
                $data,
                $this->reporte->team_id,
                $this->reporte->created_by,
                $this->reporte->diagnostico,
            );

            $this->reporte->update(['programa_presupuestario_id' => $programa->id]);
            $this->programaId = $programa->id;
        } else {
            $this->programaId = $this->reporte->programa_presupuestario_id;
        }

        $this->loadNiveles();
    }

    public function corregirCampo(int $indicadorId, string $campo, $valor): void
    {
        $allowedFields = ['formula_texto', 'tipo', 'dimension', 'frecuencia'];

        if (! in_array($campo, $allowedFields, true)) {
            return;
        }

        $indicador = Indicador::findOrFail($indicadorId);

        // Verify the indicator belongs to this programa
        $nivel = MirNivel::find($indicador->mir_nivel_id);
        if (! $nivel || $nivel->programa_presupuestario_id !== $this->programaId) {
            return;
        }

        $indicador->update([$campo => $valor]);

        // Recheck critical fields to toggle activo_seguimiento
        $indicador->refresh();
        $allCriticalFilled = $this->allCriticalFieldsFilled($indicador);
        $indicador->update(['activo_seguimiento' => $allCriticalFilled]);

        $this->loadNiveles();
    }

    public function finalizar(): void
    {
        $this->reporte->update(['estado' => 'procesado']);

        $this->redirect(route('mml.importar.vincular', ['importacion' => $this->reporte->id]));
    }

    public function progreso(): int
    {
        $total = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->programaId))->count();

        if ($total === 0) {
            return 100;
        }

        $activos = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->programaId))
            ->where('activo_seguimiento', true)
            ->count();

        return (int) round(($activos / $total) * 100);
    }

    public function render()
    {
        return view('livewire.mml.completar-huecos', [
            'progreso' => $this->progreso(),
        ]);
    }

    private function loadNiveles(): void
    {
        $this->niveles = MirNivel::where('programa_presupuestario_id', $this->programaId)
            ->with(['indicadores' => fn ($q) => $q->orderByRaw('activo_seguimiento ASC, orden ASC')])
            ->orderBy('tipo_nivel')
            ->orderBy('orden')
            ->get()
            ->map(fn (MirNivel $nivel) => [
                'id' => $nivel->id,
                'tipo_nivel' => $nivel->tipo_nivel->value ?? $nivel->tipo_nivel,
                'tipo_nivel_label' => $nivel->tipo_nivel instanceof TipoNivelMir
                    ? $nivel->tipo_nivel->label()
                    : $nivel->tipo_nivel,
                'resumen_narrativo' => $nivel->resumen_narrativo,
                'indicadores' => $nivel->indicadores->map(fn (Indicador $ind) => [
                    'id' => $ind->id,
                    'nombre' => $ind->nombre,
                    'formula_texto' => $ind->formula_texto,
                    'tipo' => $ind->tipo?->value,
                    'dimension' => $ind->dimension?->value,
                    'frecuencia' => $ind->frecuencia?->value,
                    'sentido' => $ind->sentido?->value,
                    'activo_seguimiento' => $ind->activo_seguimiento,
                    'campos_faltantes' => $this->camposFaltantes($ind),
                ])->toArray(),
            ])->toArray();
    }

    private function allCriticalFieldsFilled(Indicador $indicador): bool
    {
        foreach (self::CRITICAL_FIELDS as $field) {
            if (empty($indicador->{$field})) {
                return false;
            }
        }

        return true;
    }

    private function camposFaltantes(Indicador $indicador): array
    {
        $faltantes = [];

        foreach (self::CRITICAL_FIELDS as $field) {
            if (empty($indicador->{$field})) {
                $faltantes[] = $field;
            }
        }

        return $faltantes;
    }
}
