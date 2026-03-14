<?php

namespace App\Livewire\Juridico;

use App\Enums\NivelJerarquiaLegal;
use App\Enums\TipoSustentoLegal;
use App\Models\Juridico\CatalogoOrdenamiento;
use App\Models\Juridico\SustentoLegalPrograma as SustentoModel;
use App\Models\ProgramaPresupuestario;
use App\Services\Juridico\ValidacionJuridicaService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FundamentoForm extends Component
{
    public ProgramaPresupuestario $programa;
    public ?SustentoModel $fundamento = null;

    public string $tipo = '';
    public string $catalogo_ordenamiento_id = '';
    public string $ordenamiento = '';
    public string $articulo = '';
    public string $descripcion = '';
    public string $nivel_jerarquia = '';
    public bool $vigente = true;

    public function mount(ProgramaPresupuestario $programa, ?SustentoModel $fundamento = null): void
    {
        $this->programa = $programa;

        if ($fundamento && $fundamento->exists) {
            $this->fundamento = $fundamento;
            $this->tipo = $fundamento->tipo->value;
            $this->catalogo_ordenamiento_id = (string) ($fundamento->catalogo_ordenamiento_id ?? '');
            $this->ordenamiento = $fundamento->ordenamiento;
            $this->articulo = $fundamento->articulo ?? '';
            $this->descripcion = $fundamento->descripcion ?? '';
            $this->nivel_jerarquia = $fundamento->nivel_jerarquia->value;
            $this->vigente = $fundamento->vigente;
        }
    }

    public function updatedCatalogoOrdenamientoId($value): void
    {
        if ($value) {
            $catalogo = CatalogoOrdenamiento::find($value);
            if ($catalogo) {
                $this->ordenamiento = $catalogo->nombre;
                $this->nivel_jerarquia = $catalogo->nivel_jerarquia->value;
            }
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'tipo' => ['required', 'in:'.implode(',', array_column(TipoSustentoLegal::cases(), 'value'))],
            'ordenamiento' => ['required', 'string', 'max:255'],
            'articulo' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'nivel_jerarquia' => ['required', 'in:'.implode(',', array_column(NivelJerarquiaLegal::cases(), 'value'))],
            'vigente' => ['boolean'],
            'catalogo_ordenamiento_id' => ['nullable', 'exists:catalogo_ordenamientos,id'],
        ]);

        $data = [
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => $validated['tipo'],
            'catalogo_ordenamiento_id' => $validated['catalogo_ordenamiento_id'] ?: null,
            'ordenamiento' => $validated['ordenamiento'],
            'articulo' => $validated['articulo'] ?: null,
            'descripcion' => $validated['descripcion'] ?: null,
            'nivel_jerarquia' => $validated['nivel_jerarquia'],
            'vigente' => $validated['vigente'],
            'team_id' => auth()->user()->currentTeam->id,
            'registrado_por' => auth()->id(),
        ];

        if ($this->fundamento && $this->fundamento->exists) {
            $this->fundamento->update($data);
            session()->flash('message', 'Fundamento actualizado correctamente.');
        } else {
            SustentoModel::create($data);
            session()->flash('message', 'Fundamento registrado correctamente.');
        }

        // Recalcular checklist
        app(ValidacionJuridicaService::class)->recalcularChecklist(
            $this->programa->id,
            config('presupuesto.ejercicio_default')
        );

        $this->redirect(route('juridico.programa', $this->programa));
    }

    public function render(): \Illuminate\View\View
    {
        $catalogoOrdenamientos = CatalogoOrdenamiento::activos()
            ->orderBy('orden')
            ->get();

        return view('livewire.juridico.fundamento-form', [
            'catalogoOrdenamientos' => $catalogoOrdenamientos,
            'tiposSustento' => TipoSustentoLegal::cases(),
            'nivelesJerarquia' => NivelJerarquiaLegal::cases(),
        ]);
    }
}
