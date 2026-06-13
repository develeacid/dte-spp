<?php

namespace App\Livewire\Mml;

use App\Models\Mml\PoblacionPrograma;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Etapa 5 — Embudo de Poblaciones')]
class EmbudoPoblaciones extends Component
{
    public ProgramaPresupuestario $programa;

    #[Validate('required|string|max:100')]
    public string $unidad_medida = '';

    #[Validate('required|integer|min:1')]
    public ?int $referencia_cantidad = null;

    #[Validate('nullable|string|max:500')]
    public string $referencia_fuente = '';

    #[Validate('required|integer|min:1')]
    public ?int $potencial_cantidad = null;

    #[Validate('nullable|string|max:500')]
    public string $potencial_fuente = '';

    #[Validate('required|integer|min:1')]
    public ?int $objetivo_cantidad = null;

    #[Validate('nullable|string|max:1000')]
    public string $objetivo_justificacion = '';

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;

        // La fila del ejercicio fiscal es la canónica (guardar() y render()
        // también la usan). Un programa puede tener varias filas (una por año),
        // así que filtramos explícitamente en vez de la relación cruda.
        $poblacion = $programa->poblacion()
            ->where('anio_ejercicio', $programa->ejercicio_fiscal)
            ->first();

        if ($poblacion) {
            $this->unidad_medida = $poblacion->unidad_medida;
            $this->referencia_cantidad = $poblacion->referencia_cantidad;
            $this->referencia_fuente = $poblacion->referencia_fuente ?? '';
            $this->potencial_cantidad = $poblacion->potencial_cantidad;
            $this->potencial_fuente = $poblacion->potencial_fuente ?? '';
            $this->objetivo_cantidad = $poblacion->objetivo_cantidad;
            $this->objetivo_justificacion = $poblacion->objetivo_justificacion ?? '';
        }
    }

    public function guardar(): void
    {
        $this->validate();

        // Validación del embudo: objetivo <= potencial <= referencia
        if ($this->potencial_cantidad > $this->referencia_cantidad) {
            $this->addError('potencial_cantidad',
                "La Población Potencial ({$this->potencial_cantidad}) no puede ser mayor a la de Referencia ({$this->referencia_cantidad}).");

            return;
        }

        if ($this->objetivo_cantidad > $this->potencial_cantidad) {
            $this->addError('objetivo_cantidad',
                "La Población Objetivo ({$this->objetivo_cantidad}) no puede ser mayor a la Potencial ({$this->potencial_cantidad}). Revisa las cifras o ajusta la Población Objetivo.");

            return;
        }

        PoblacionPrograma::updateOrCreate(
            [
                'programa_id' => $this->programa->id,
                'anio_ejercicio' => $this->programa->ejercicio_fiscal,
            ],
            [
                'unidad_medida' => $this->unidad_medida,
                'referencia_cantidad' => $this->referencia_cantidad,
                'referencia_fuente' => $this->referencia_fuente ?: null,
                'potencial_cantidad' => $this->potencial_cantidad,
                'potencial_fuente' => $this->potencial_fuente ?: null,
                'objetivo_cantidad' => $this->objetivo_cantidad,
                'objetivo_justificacion' => $this->objetivo_justificacion ?: null,
            ]
        );

        session()->flash('success', 'Poblaciones guardadas correctamente.');
    }

    public function render()
    {
        // Población persistida del ejercicio (incluye la atendida real
        // sincronizada desde geobase vía geobase:sync-atendida).
        $poblacion = $this->programa->poblacion()
            ->where('anio_ejercicio', $this->programa->ejercicio_fiscal)
            ->first();

        return view('livewire.mml.embudo-poblaciones', [
            'poblacion' => $poblacion,
        ]);
    }
}
