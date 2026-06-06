<?php

namespace App\Livewire\Presupuesto;

use App\Models\Presupuesto\AvanceFinanciero;
use App\Models\Presupuesto\MetaGastoTrimestral;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CapturaAvanceFinanciero extends Component
{
    public ProgramaPresupuestario $programa;

    public string $seccion = 'calendarizacion'; // calendarizacion | avance

    // Calendarización: metas[partida_id][trimestre] = monto_programado
    public array $metas = [];

    // Avance: avances[partida_id][trimestre] = [comprometido, devengado, pagado, observaciones]
    public array $avances = [];

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
        $this->cargarDatos();
    }

    private function cargarDatos(): void
    {
        $partidas = PartidaPresupuestal::where('programa_presupuestario_id', $this->programa->id)
            ->paraTeam(auth()->user()->currentTeam->id)
            ->paraEjercicio($this->programa->ejercicio_fiscal)
            ->with(['metasGasto', 'avancesFinancieros'])
            ->orderBy('clave_partida')
            ->get();

        foreach ($partidas as $partida) {
            for ($t = 1; $t <= 4; $t++) {
                $meta = $partida->metasGasto->firstWhere('trimestre', $t);
                $this->metas[$partida->id][$t] = $meta ? (string) $meta->monto_programado : '';

                $avance = $partida->avancesFinancieros->firstWhere('trimestre', $t);
                $this->avances[$partida->id][$t] = [
                    'comprometido' => $avance ? (string) $avance->monto_comprometido : '',
                    'devengado' => $avance ? (string) $avance->monto_devengado : '',
                    'pagado' => $avance ? (string) $avance->monto_pagado : '',
                    'observaciones' => $avance?->observaciones ?? '',
                ];
            }
        }
    }

    public function guardarMetas(): void
    {
        foreach ($this->metas as $partidaId => $trimestres) {
            foreach ($trimestres as $trimestre => $monto) {
                if ($monto === '' || $monto === null) {
                    MetaGastoTrimestral::where('partida_presupuestal_id', $partidaId)
                        ->where('trimestre', $trimestre)
                        ->delete();

                    continue;
                }

                $this->validate([
                    "metas.{$partidaId}.{$trimestre}" => ['numeric', 'min:0'],
                ]);

                MetaGastoTrimestral::updateOrCreate(
                    ['partida_presupuestal_id' => $partidaId, 'trimestre' => $trimestre],
                    ['monto_programado' => $monto]
                );
            }
        }

        session()->flash('message', 'Metas de gasto calendarizadas guardadas correctamente.');
        $this->cargarDatos();
    }

    public function guardarAvances(): void
    {
        foreach ($this->avances as $partidaId => $trimestres) {
            foreach ($trimestres as $trimestre => $datos) {
                $comprometido = $datos['comprometido'];
                $devengado = $datos['devengado'];
                $pagado = $datos['pagado'];

                // Saltar celdas vacías
                if ($comprometido === '' && $devengado === '' && $pagado === '') {
                    continue;
                }

                $this->validate([
                    "avances.{$partidaId}.{$trimestre}.comprometido" => ['required', 'numeric', 'min:0'],
                    "avances.{$partidaId}.{$trimestre}.devengado" => ['required', 'numeric', 'min:0'],
                    "avances.{$partidaId}.{$trimestre}.pagado" => ['required', 'numeric', 'min:0'],
                ], [
                    'required' => 'Todos los montos del trimestre son obligatorios.',
                    'min' => 'Los montos no pueden ser negativos.',
                ]);

                // Validar ordenamiento: pagado ≤ devengado ≤ comprometido
                if ((float) $pagado > (float) $devengado) {
                    $this->addError("avances.{$partidaId}.{$trimestre}.pagado", 'Pagado no puede ser mayor que devengado.');

                    return;
                }
                if ((float) $devengado > (float) $comprometido) {
                    $this->addError("avances.{$partidaId}.{$trimestre}.devengado", 'Devengado no puede ser mayor que comprometido.');

                    return;
                }

                AvanceFinanciero::updateOrCreate(
                    ['partida_presupuestal_id' => $partidaId, 'trimestre' => $trimestre],
                    [
                        'monto_comprometido' => $comprometido,
                        'monto_devengado' => $devengado,
                        'monto_pagado' => $pagado,
                        'registrado_por' => auth()->id(),
                        'observaciones' => $datos['observaciones'] ?: null,
                    ]
                );
            }
        }

        session()->flash('message', 'Avances financieros guardados correctamente.');
        $this->cargarDatos();
    }

    public function render(): View
    {
        $partidas = PartidaPresupuestal::where('programa_presupuestario_id', $this->programa->id)
            ->paraTeam(auth()->user()->currentTeam->id)
            ->paraEjercicio($this->programa->ejercicio_fiscal)
            ->orderBy('clave_partida')
            ->get();

        $totalEfectivo = (float) $partidas->sum('monto_efectivo');
        $totalProgramado = 0.0;
        $totalPagado = 0.0;
        foreach ($this->metas as $trimestres) {
            foreach ($trimestres as $monto) {
                $totalProgramado += (float) $monto;
            }
        }
        foreach ($this->avances as $trimestres) {
            foreach ($trimestres as $datos) {
                $totalPagado += (float) ($datos['pagado'] ?? 0);
            }
        }
        $pctPagado = $totalEfectivo > 0 ? round(($totalPagado / $totalEfectivo) * 100, 1) : 0;

        $kpis = [
            ['label' => 'Partidas', 'value' => $partidas->count()],
            ['label' => 'Efectivo', 'value' => '$'.number_format($totalEfectivo, 0), 'color' => 'blue'],
            ['label' => 'Programado (T1-T4)', 'value' => '$'.number_format($totalProgramado, 0), 'color' => 'amber'],
            ['label' => '% Pagado', 'value' => $pctPagado.'%', 'color' => $pctPagado >= 75 ? 'green' : ($pctPagado >= 40 ? 'amber' : 'red')],
        ];

        return view('livewire.presupuesto.captura-avance-financiero', [
            'partidas' => $partidas,
            'kpis' => $kpis,
        ]);
    }
}
