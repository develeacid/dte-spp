<?php

namespace App\Livewire\Tracking;

use App\Enums\EstadoAvance;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PanelSeguimiento extends Component
{
    public ?int $filtroPrograma = null;

    public ?string $filtroEstado = null;

    public ?string $filtroSemaforo = null;

    /** @var array<int> */
    public array $expandido = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('revisar_avance'), 403);
    }

    public function toggleExpandir(int $indicadorId): void
    {
        if (in_array($indicadorId, $this->expandido)) {
            $this->expandido = array_values(array_diff($this->expandido, [$indicadorId]));
        } else {
            $this->expandido[] = $indicadorId;
        }
    }

    public function render()
    {
        $teamId = auth()->user()->currentTeam->id;

        $programas = ProgramaPresupuestario::paraTeam($teamId)
            ->orderBy('nombre')
            ->get();

        // Build query for MirNiveles belonging to current team
        $nivelesQuery = MirNivel::query()
            ->where(function ($q) use ($teamId) {
                // Programas owned by team
                $q->whereHas('programa', fn ($p) => $p->where('team_id', $teamId))
                    // OR UR Coadyuvante: mir_niveles.team_id = currentTeam
                    ->orWhere('team_id', $teamId);
            })
            ->with([
                'programa:id,nombre,clave',
                'indicadores' => fn ($q) => $q->where('activo_seguimiento', true),
                'indicadores.metasPeriodo' => fn ($q) => $q->orderByDesc('periodo'),
                'indicadores.metasPeriodo.avance.variables.indicadorVariable',
                'indicadores.metasPeriodo.avance.evidencias',
                'indicadores.variables',
                'indicadores.anexosTransversales',
            ]);

        if ($this->filtroPrograma) {
            $nivelesQuery->where('programa_presupuestario_id', $this->filtroPrograma);
        }

        $niveles = $nivelesQuery->orderBy('tipo_nivel')->orderBy('orden')->get();

        // Build flat rows for the table
        $filas = collect();

        foreach ($niveles as $nivel) {
            foreach ($nivel->indicadores as $indicador) {
                // Get the latest meta_periodo that has an avance
                $ultimaMetaConAvance = $indicador->metasPeriodo
                    ->first(fn ($mp) => $mp->avance !== null);

                $avance = $ultimaMetaConAvance?->avance;
                $metaPeriodo = $ultimaMetaConAvance;

                $semaforo = $avance?->semaforo_calculado ?? 'gris';
                $estado = $avance?->estado;

                // Apply filters
                if ($this->filtroEstado && ($estado?->value ?? null) !== $this->filtroEstado) {
                    continue;
                }
                if ($this->filtroSemaforo && $semaforo !== $this->filtroSemaforo) {
                    continue;
                }

                $filas->push([
                    'programa_nombre' => $nivel->programa?->nombre ?? '—',
                    'programa_clave' => $nivel->programa?->clave ?? '—',
                    'nivel_tipo' => $nivel->tipo_nivel,
                    'indicador_id' => $indicador->id,
                    'indicador_nombre' => $indicador->nombre,
                    'meta' => $metaPeriodo?->meta_periodo,
                    'resultado' => $avance?->resultado,
                    'semaforo' => $semaforo,
                    'estado' => $estado,
                    'avance' => $avance,
                    'indicador' => $indicador,
                ]);
            }
        }

        return view('livewire.tracking.panel-seguimiento', [
            'programas' => $programas,
            'filas' => $filas,
            'estadosAvance' => EstadoAvance::cases(),
        ]);
    }
}
