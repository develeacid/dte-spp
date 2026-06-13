<?php

namespace App\Livewire\Mml;

use App\Models\Presupuesto\ClasificacionFuncional;
use App\Models\ProgramaPresupuestario;
use App\Services\Presupuesto\ClavePresupuestalService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Clave presupuestal')]
class ClavePresupuestalEditor extends Component
{
    public ProgramaPresupuestario $programa;

    // Bloque Administrativa.
    public ?int $grupo = null;

    public ?int $unidad_responsable = null;

    public ?int $unidad_ejecutora = null;

    // Bloque Programática.
    public ?int $programa_clave = null;

    public ?int $subprograma = null;

    public ?int $proyecto = null;

    public ?int $actividad = null;

    // Clasificación Funcional CONAC.
    public ?int $finalidad_id = null;

    public ?int $funcion_id = null;

    public ?int $subfuncion_id = null;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->authorize('editar_mir');

        $this->programa = $programa;
        foreach (['grupo', 'unidad_responsable', 'unidad_ejecutora', 'programa_clave',
            'subprograma', 'proyecto', 'actividad', 'finalidad_id', 'funcion_id', 'subfuncion_id'] as $campo) {
            $this->{$campo} = $programa->{$campo};
        }
    }

    public function updatedFinalidadId(): void
    {
        $this->funcion_id = null;
        $this->subfuncion_id = null;
    }

    public function updatedFuncionId(): void
    {
        $this->subfuncion_id = null;
    }

    #[Computed]
    public function finalidadesDisponibles()
    {
        return ClasificacionFuncional::finalidades()->orderBy('clave')->get();
    }

    #[Computed]
    public function funcionesDisponibles()
    {
        if ($this->finalidad_id === null) {
            return collect();
        }

        return ClasificacionFuncional::funciones()->where('padre_id', $this->finalidad_id)->orderBy('clave')->get();
    }

    #[Computed]
    public function subfuncionesDisponibles()
    {
        if ($this->funcion_id === null) {
            return collect();
        }

        return ClasificacionFuncional::subfunciones()->where('padre_id', $this->funcion_id)->orderBy('clave')->get();
    }

    #[Computed]
    public function previewClave(): ?string
    {
        return $this->programaTransitorio()->clave_presupuestal_canonica;
    }

    public function guardar(): void
    {
        $this->authorize('editar_mir');

        $errores = ClavePresupuestalService::validar($this->programaTransitorio());

        if ($errores !== []) {
            foreach ($errores as $error) {
                $this->addError($this->campoDeError($error), $error);
            }

            return;
        }

        foreach (['grupo', 'unidad_responsable', 'unidad_ejecutora', 'programa_clave',
            'subprograma', 'proyecto', 'actividad', 'finalidad_id', 'funcion_id', 'subfuncion_id'] as $campo) {
            $this->programa->{$campo} = $this->{$campo};
        }
        $this->programa->save();

        session()->flash('success', 'Clave presupuestal guardada.');
    }

    private function programaTransitorio(): ProgramaPresupuestario
    {
        $programa = new ProgramaPresupuestario;
        foreach (['grupo', 'unidad_responsable', 'unidad_ejecutora', 'programa_clave',
            'subprograma', 'proyecto', 'actividad', 'finalidad_id', 'funcion_id', 'subfuncion_id'] as $campo) {
            $programa->{$campo} = $this->{$campo};
        }

        return $programa;
    }

    /** Mapea un mensaje de error del servicio al campo del formulario (específico → genérico). */
    private function campoDeError(string $error): string
    {
        return match (true) {
            str_contains($error, 'subfunción') => 'subfuncion_id',
            str_contains($error, 'función') => 'funcion_id',
            str_contains($error, 'Grupo') => 'grupo',
            str_contains($error, 'Unidad Responsable') => 'unidad_responsable',
            str_contains($error, 'Unidad Ejecutora') => 'unidad_ejecutora',
            str_contains($error, 'Subprograma') => 'subprograma',
            str_contains($error, 'Programa') => 'programa_clave',
            str_contains($error, 'Proyecto') => 'proyecto',
            str_contains($error, 'Actividad') => 'actividad',
            default => 'clave_presupuestal',
        };
    }

    public function render()
    {
        return view('livewire.mml.clave-presupuestal-editor');
    }
}
