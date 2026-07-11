<?php

namespace App\Livewire\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\InvolucradoCategoria;
use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\Mml\FichaInformacionBasica;
use App\Models\Mml\Involucrado;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Etapa 1 — Definición del Problema')]
class DefinicionProblema extends Component
{
    public ProgramaPresupuestario $programa;

    public string $descripcion = '';

    public string $magnitud = '';

    public string $focalizacion = '';

    public string $causasEfectos = '';

    public string $bienesServicios = '';

    public string $sugerenciaIa = '';

    public bool $validandoConIa = false;

    public ?array $resultadoValidacion = null;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;

        $arbol = $programa->arboles()->where('tipo', TipoArbol::PROBLEMA->value)->first();
        if ($arbol) {
            $nodoCentral = $arbol->nodos()->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)->first();
            if ($nodoCentral) {
                $this->descripcion = $nodoCentral->descripcion;
            }
        }

        $ficha = $programa->fichaInformacionBasica;
        if ($ficha) {
            $this->magnitud = $ficha->magnitud ?? '';
            $this->focalizacion = $ficha->focalizacion ?? '';
            $this->causasEfectos = $ficha->causas_efectos ?? '';
            $this->bienesServicios = $ficha->bienes_servicios ?? '';
        }
    }

    #[Computed]
    public function completitud(): int
    {
        return collect([
            $this->descripcion,
            $this->magnitud,
            $this->focalizacion,
            $this->causasEfectos,
            $this->bienesServicios,
        ])->filter(fn ($v) => trim((string) $v) !== '')->count();
    }

    public function validarConIa(): void
    {
        $this->validate([
            'descripcion' => 'required|min:20',
        ]);

        $this->validandoConIa = true;
        $this->resultadoValidacion = null;
        $this->sugerenciaIa = '';

        try {
            $llm = app(LlmServiceInterface::class);
            $result = $llm->validateProblema($this->descripcion);

            $this->resultadoValidacion = $result->toArray();
            $this->sugerenciaIa = $result->suggestion;
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo conectar con el servicio de IA. Puedes continuar sin validación.');
        } finally {
            $this->validandoConIa = false;
        }
    }

    public function aceptarSugerencia(): void
    {
        if (! empty($this->sugerenciaIa)) {
            $this->descripcion = $this->sugerenciaIa;
            $this->sugerenciaIa = '';
            $this->resultadoValidacion = null;
        }
    }

    public function guardar(): void
    {
        $this->validate([
            'descripcion' => 'required|min:20|max:1000',
            'magnitud' => 'nullable|string|max:2000',
            'focalizacion' => 'nullable|string|max:2000',
            'causasEfectos' => 'nullable|string|max:2000',
            'bienesServicios' => 'nullable|string|max:2000',
        ]);

        $arbol = Arbol::firstOrCreate(
            [
                'programa_presupuestario_id' => $this->programa->id,
                'tipo' => TipoArbol::PROBLEMA->value,
            ]
        );

        ArbolNodo::updateOrCreate(
            [
                'arbol_id' => $arbol->id,
                'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            ],
            [
                'descripcion' => $this->descripcion,
            ]
        );

        FichaInformacionBasica::updateOrCreate(
            ['programa_presupuestario_id' => $this->programa->id],
            [
                'magnitud' => $this->magnitud ?: null,
                'focalizacion' => $this->focalizacion ?: null,
                'causas_efectos' => $this->causasEfectos ?: null,
                'bienes_servicios' => $this->bienesServicios ?: null,
            ]
        );

        session()->flash('success', 'Problema central guardado correctamente.');
    }

    private function involucradoDelPrograma(int $id): ?Involucrado
    {
        return Involucrado::where('programa_presupuestario_id', $this->programa->id)->find($id);
    }

    public function agregarInvolucrado(): void
    {
        $maxOrden = $this->programa->involucrados()->max('orden') ?? 0;

        Involucrado::create([
            'programa_presupuestario_id' => $this->programa->id,
            'categoria' => InvolucradoCategoria::BENEFICIARIO_DIRECTO->value,
            'nombre' => '',
            'orden' => $maxOrden + 1,
        ]);
    }

    public function guardarInvolucrado(int $id, array $data): void
    {
        $involucrado = $this->involucradoDelPrograma($id);

        if ($involucrado === null) {
            return;
        }

        $validated = validator($data, [
            'categoria' => 'required|in:'.implode(',', InvolucradoCategoria::values()),
            'nombre' => 'required|string|max:255',
            'interes_o_rol' => 'nullable|string|max:1000',
            'riesgo_asociado' => 'nullable|string|max:1000',
        ])->validate();

        $involucrado->update($validated);
    }

    public function eliminarInvolucrado(int $id): void
    {
        $involucrado = $this->involucradoDelPrograma($id);

        if ($involucrado === null) {
            return;
        }

        $involucrado->delete();
    }

    public function render()
    {
        return view('livewire.mml.definicion-problema');
    }
}
