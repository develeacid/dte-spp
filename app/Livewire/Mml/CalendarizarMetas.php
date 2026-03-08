<?php

namespace App\Livewire\Mml;

use App\Enums\EstadoPrograma;
use App\Models\Mml\ImportacionReporte;
use App\Models\ProgramaPresupuestario;
use App\Services\Mml\CalendarizacionService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Calendarizar Metas — MIR Importada')]
class CalendarizarMetas extends Component
{
    public ImportacionReporte $reporte;

    public ?ProgramaPresupuestario $programa = null;

    /** @var array Proposed period goals from service */
    public array $propuesta = [];

    public function mount(ImportacionReporte $importacion): void
    {
        $this->reporte = $importacion;
        $this->programa = $this->reporte->programa;

        if ($this->programa) {
            $service = app(CalendarizacionService::class);
            $this->propuesta = $service->generar($this->programa);
        }
    }

    /**
     * Adjust a specific period goal in-memory.
     */
    public function ajustarMeta(int $indicadorId, int $periodo, float $valor): void
    {
        foreach ($this->propuesta as &$indicador) {
            if ($indicador['indicador_id'] === $indicadorId) {
                foreach ($indicador['periodos'] as &$p) {
                    if ($p['periodo'] === $periodo) {
                        $p['meta_periodo'] = round($valor, 4);
                        break 2;
                    }
                }
            }
        }
        unset($indicador, $p);
    }

    /**
     * Confirm and persist period goals, activate the programa.
     */
    public function confirmar(): void
    {
        if (!$this->programa) {
            return;
        }

        $service = app(CalendarizacionService::class);
        $service->confirmar(
            $this->programa,
            $this->propuesta,
            $this->programa->ejercicio_fiscal,
        );

        $this->programa->update(['estado' => EstadoPrograma::ACTIVO]);

        $this->redirect(route('dashboard'));
    }

    public function render()
    {
        // Build warning flags for indicators where sum != annual goal
        $warnings = [];
        foreach ($this->propuesta as $indicador) {
            $suma = collect($indicador['periodos'])->sum('meta_periodo');
            $meta = (float) $indicador['meta'];
            if (abs($suma - $meta) > 0.001) {
                $warnings[$indicador['indicador_id']] = [
                    'suma' => round($suma, 4),
                    'meta' => $meta,
                ];
            }
        }

        return view('livewire.mml.calendarizar-metas', [
            'warnings' => $warnings,
        ]);
    }
}
