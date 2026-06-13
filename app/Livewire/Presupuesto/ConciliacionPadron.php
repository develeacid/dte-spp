<?php

namespace App\Livewire\Presupuesto;

use App\Models\Presupuesto\AvanceFinanciero;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Conciliación físico-financiera (V2-D6): cruza la tesorería local (pagado)
 * contra los montos efectivamente entregados del padrón (geobase, en vivo).
 */
#[Layout('layouts.app')]
#[Title('Conciliación físico-financiera')]
class ConciliacionPadron extends Component
{
    public ProgramaPresupuestario $programa;

    public int $ejercicio;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->authorize('ver_datos_financieros');

        $this->programa = $programa;
        $this->ejercicio = (int) ($programa->ejercicio_fiscal ?? now()->year);
    }

    public function render(GeoBaseClient $geobase)
    {
        // Tesorería local: total pagado de las partidas del programa en el ejercicio.
        $pagado = (float) AvanceFinanciero::query()
            ->join('partidas_presupuestales as p', 'p.id', '=', 'avances_financieros.partida_presupuestal_id')
            ->where('p.programa_presupuestario_id', $this->programa->id)
            ->where('p.ejercicio_fiscal', $this->ejercicio)
            ->sum('avances_financieros.monto_pagado');

        $entregado = null;
        $porComponente = [];
        $error = null;

        try {
            $resp = $geobase->getMontosEntregados($this->programa->id, $this->ejercicio);
            $entregado = (float) ($resp['monto_entregado_total'] ?? 0);
            $porComponente = $resp['por_componente'] ?? [];
        } catch (\Throwable $e) {
            $error = 'No se pudo consultar el padrón en geobase. Mostrando solo la tesorería local.';
        }

        $diferencia = $entregado !== null ? $pagado - $entregado : null;

        return view('livewire.presupuesto.conciliacion-padron', [
            'pagado' => $pagado,
            'entregado' => $entregado,
            'diferencia' => $diferencia,
            'porComponente' => $porComponente,
            'error' => $error,
        ]);
    }
}
