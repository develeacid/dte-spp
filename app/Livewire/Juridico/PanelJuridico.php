<?php

namespace App\Livewire\Juridico;

use App\Enums\EstadoValidacionJuridica;
use App\Models\Juridico\DocumentoNormativo;
use App\Models\ProgramaPresupuestario;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PanelJuridico extends Component
{
    public int $filtroEjercicio;

    public string $filtroEstado = '';

    public function mount(): void
    {
        $this->filtroEjercicio = config('presupuesto.ejercicio_default');
    }

    public function render(): View
    {
        $teamId = auth()->user()->currentTeam->id;

        $programas = ProgramaPresupuestario::paraTeam($teamId)
            ->ejercicio($this->filtroEjercicio)
            ->with('validacionJuridica')
            ->orderBy('clave')
            ->get();

        // KPIs
        $validados = 0;
        $pendientes = 0;
        $rechazados = 0;
        $sinRegistro = 0;

        foreach ($programas as $programa) {
            $estado = $programa->validacionJuridica?->estado;
            match ($estado) {
                EstadoValidacionJuridica::VALIDADO => $validados++,
                EstadoValidacionJuridica::RECHAZADO => $rechazados++,
                EstadoValidacionJuridica::PENDIENTE,
                EstadoValidacionJuridica::EN_REVISION => $pendientes++,
                default => $sinRegistro++,
            };
        }

        // Filtro por estado
        if ($this->filtroEstado) {
            $programas = $programas->filter(function ($programa) {
                $estado = $programa->validacionJuridica?->estado?->value;

                return match ($this->filtroEstado) {
                    'sin_registro' => $estado === null,
                    default => $estado === $this->filtroEstado,
                };
            });
        }

        // Alertas de vigencia
        $diasAlerta = config('juridico.dias_alerta_vigencia', 30);
        $documentosProximosVencer = DocumentoNormativo::where('team_id', $teamId)
            ->whereNotNull('fecha_vigencia')
            ->where('fecha_vigencia', '<=', now()->addDays($diasAlerta))
            ->where('fecha_vigencia', '>', now())
            ->count();

        $kpis = [
            ['label' => 'Validados', 'value' => $validados, 'color' => 'green'],
            ['label' => 'Pendientes', 'value' => $pendientes, 'color' => 'amber'],
            ['label' => 'Rechazados', 'value' => $rechazados, 'color' => 'red'],
            ['label' => 'Sin registro', 'value' => $sinRegistro],
        ];

        return view('livewire.juridico.panel-juridico', [
            'programas' => $programas,
            'validados' => $validados,
            'pendientes' => $pendientes,
            'rechazados' => $rechazados,
            'sinRegistro' => $sinRegistro,
            'documentosProximosVencer' => $documentosProximosVencer,
            'kpis' => $kpis,
        ]);
    }
}
