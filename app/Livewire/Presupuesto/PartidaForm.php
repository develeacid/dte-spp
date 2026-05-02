<?php

namespace App\Livewire\Presupuesto;

use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PartidaForm extends Component
{
    public ?PartidaPresupuestal $partida = null;

    public string $programa_presupuestario_id = '';

    public string $clave_partida = '';

    public string $descripcion = '';

    public string $monto_aprobado = '';

    public string $monto_modificado = '';

    public int $ejercicio_fiscal;

    public function mount(?PartidaPresupuestal $partida = null): void
    {
        $this->ejercicio_fiscal = config('presupuesto.ejercicio_default');

        if ($partida && $partida->exists) {
            $this->partida = $partida;
            $this->programa_presupuestario_id = (string) $partida->programa_presupuestario_id;
            $this->clave_partida = $partida->clave_partida;
            $this->descripcion = $partida->descripcion;
            $this->monto_aprobado = $partida->monto_aprobado;
            $this->monto_modificado = $partida->monto_modificado ?? '';
            $this->ejercicio_fiscal = $partida->ejercicio_fiscal;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'programa_presupuestario_id' => ['required', 'exists:programa_presupuestarios,id'],
            'clave_partida' => ['required', 'string', 'max:20'],
            'descripcion' => ['required', 'string', 'max:255'],
            'monto_aprobado' => ['required', 'numeric', 'min:0'],
            'monto_modificado' => ['nullable', 'numeric', 'min:0'],
            'ejercicio_fiscal' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $data = [
            'programa_presupuestario_id' => $validated['programa_presupuestario_id'],
            'clave_partida' => $validated['clave_partida'],
            'descripcion' => $validated['descripcion'],
            'monto_aprobado' => $validated['monto_aprobado'],
            'monto_modificado' => $validated['monto_modificado'] ?: null,
            'ejercicio_fiscal' => $validated['ejercicio_fiscal'],
            'team_id' => auth()->user()->currentTeam->id,
            'registrado_por' => auth()->id(),
        ];

        if ($this->partida && $this->partida->exists) {
            $this->partida->update($data);
            session()->flash('message', 'Partida actualizada correctamente.');
        } else {
            PartidaPresupuestal::create($data);
            session()->flash('message', 'Partida creada correctamente.');
        }

        $this->redirect(route('presupuesto.partidas'));
    }

    public function render(): View
    {
        $teamId = auth()->user()->currentTeam->id;

        $programas = ProgramaPresupuestario::paraTeam($teamId)
            ->ejercicio($this->ejercicio_fiscal)
            ->orderBy('clave')
            ->get();

        return view('livewire.presupuesto.partida-form', [
            'programas' => $programas,
        ]);
    }
}
