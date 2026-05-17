<?php

namespace App\Livewire\Mml;

use App\Enums\TipoNivelMir;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CoberturaPrograma extends Component
{
    public ProgramaPresupuestario $programa;

    public string $estado = 'ok';

    public ?string $errorMessage = null;

    public array $coverage = [];

    /** @var array<int, array{tipo: string, narrativa: string, supuestos: ?string}> */
    public array $supuestos = [];

    public ?string $consultadoAt = null;

    /** Alerta vs trimestre anterior: ['nivel' => 'amarillo'|'rojo', 'drop_pct' => int] o null. */
    public ?array $alertaTrimestre = null;

    /** Alerta vs meta poblacion_objetivo: ['nivel' => 'amarillo'|'rojo', 'pct' => int] o null. */
    public ?array $alertaMeta = null;

    /** @var array<int, array{municipality: string, drop_pct: int}> Municipios con drop >30% vs Q anterior. */
    public array $municipiosConDrop = [];

    /** null = todo el periodo. Formato cuando set: 'YYYY-QN' (ej. '2026-Q2'). */
    public ?string $periodoSeleccionado = null;

    /** @var array<int, string> Q actual + 3 previos, formato 'YYYY-QN', del más reciente al más viejo. */
    public array $periodosDisponibles = [];

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->authorize('ver_padron');
        $this->programa = $programa;

        $this->periodosDisponibles = $this->calcularPeriodosDisponibles();
        $this->cargarSupuestos();
        $this->cargarCoverage();
    }

    public function seleccionarPeriodo(?string $periodo): void
    {
        if ($periodo !== null && ! in_array($periodo, $this->periodosDisponibles, true)) {
            return;
        }
        $this->periodoSeleccionado = $periodo;
        $this->cargarCoverage();
    }

    /** @return array<int, string> */
    private function calcularPeriodosDisponibles(): array
    {
        $now = Carbon::now();
        $periodos = [];
        for ($i = 0; $i < 4; $i++) {
            $ref = $now->copy()->subMonthsNoOverflow($i * 3);
            $periodos[] = sprintf('%d-Q%d', $ref->year, (int) ceil($ref->month / 3));
        }

        return $periodos;
    }

    private function cargarCoverage(): void
    {
        // Reset alertas (en caso de re-fetch tras seleccionarPeriodo)
        $this->errorMessage = null;
        $this->alertaTrimestre = null;
        $this->alertaMeta = null;
        $this->municipiosConDrop = [];
        $this->coverage = [];

        if (! $this->programa->padron_geobase_activo) {
            $this->estado = 'inactivo';

            return;
        }

        try {
            $client = app(GeoBaseClient::class);
            $this->coverage = $client->getProgramCoverage($this->programa->id, $this->periodoSeleccionado);
            $this->consultadoAt = now()->format('Y-m-d H:i');
            $this->estado = ((int) ($this->coverage['total_beneficiaries'] ?? 0)) === 0
                ? 'vacio'
                : 'ok';

            $this->calcularAlertas($client);
        } catch (GeoBaseException $e) {
            if ($e->statusCode === 404) {
                $this->estado = 'no_registrado';

                return;
            }
            $this->estado = 'error';
            $this->errorMessage = 'GeoBase no está disponible en este momento. Reintentar.';
        }
    }

    private function cargarSupuestos(): void
    {
        $niveles = $this->programa->mirNiveles()
            ->whereIn('tipo_nivel', [TipoNivelMir::PROPOSITO, TipoNivelMir::COMPONENTE])
            ->orderByRaw("CASE tipo_nivel WHEN 'proposito' THEN 0 WHEN 'componente' THEN 1 ELSE 2 END")
            ->orderBy('orden')
            ->get(['tipo_nivel', 'resumen_narrativo', 'supuestos']);

        $this->supuestos = $niveles->map(fn ($n) => [
            'tipo' => $n->tipo_nivel instanceof TipoNivelMir ? $n->tipo_nivel->value : (string) $n->tipo_nivel,
            'narrativa' => (string) $n->resumen_narrativo,
            'supuestos' => $n->supuestos,
        ])->toArray();
    }

    private function calcularAlertas(GeoBaseClient $client): void
    {
        // Si hay periodo seleccionado, las alertas comparan ese Q vs el Q anterior a ese.
        // Si no, comparan Q actual (now) vs Q anterior — comportamiento legacy.
        if ($this->periodoSeleccionado !== null) {
            $qActual = $this->periodoSeleccionado;
            $qAnterior = $this->periodoAnteriorA($this->periodoSeleccionado);
        } else {
            $now = Carbon::now();
            $qActual = sprintf('%d-Q%d', $now->year, (int) ceil($now->month / 3));
            $prev = $now->copy()->subMonthsNoOverflow(3);
            $qAnterior = sprintf('%d-Q%d', $prev->year, (int) ceil($prev->month / 3));
        }

        $coverageActual = $this->fetchCoverageSafe($client, $qActual);
        $coverageAnterior = $this->fetchCoverageSafe($client, $qAnterior);

        $this->alertaTrimestre = $this->calcularAlertaTrimestre($coverageActual, $coverageAnterior);
        $this->alertaMeta = $this->calcularAlertaMeta();
        $this->municipiosConDrop = $this->calcularMunicipiosConDrop($coverageActual, $coverageAnterior);
    }

    private function periodoAnteriorA(string $periodo): string
    {
        // Parse 'YYYY-QN' (N en 1..4) y resta un trimestre.
        if (! preg_match('/^(\d{4})-Q([1-4])$/', $periodo, $m)) {
            return $periodo;
        }
        $year = (int) $m[1];
        $q = (int) $m[2];
        if ($q === 1) {
            return sprintf('%d-Q4', $year - 1);
        }

        return sprintf('%d-Q%d', $year, $q - 1);
    }

    /** @return array|null null si geobase responde 404 (sin datos para ese periodo). */
    private function fetchCoverageSafe(GeoBaseClient $client, string $period): ?array
    {
        try {
            return $client->getProgramCoverage($this->programa->id, $period);
        } catch (GeoBaseException $e) {
            if ($e->statusCode === 404) {
                return null;
            }
            throw $e;
        }
    }

    private function calcularAlertaTrimestre(?array $actual, ?array $anterior): ?array
    {
        if ($actual === null || $anterior === null) {
            return null;
        }
        $valActual = (int) ($actual['total_beneficiaries'] ?? 0);
        $valAnterior = (int) ($anterior['total_beneficiaries'] ?? 0);
        if ($valAnterior === 0 || $valActual >= $valAnterior) {
            return null;
        }
        $dropPct = (int) round((($valAnterior - $valActual) / $valAnterior) * 100);
        if ($dropPct < 10) {
            return null;
        }

        return [
            'nivel' => $dropPct >= 25 ? 'rojo' : 'amarillo',
            'drop_pct' => $dropPct,
        ];
    }

    private function calcularAlertaMeta(): ?array
    {
        $poblacion = $this->programa->poblacion;
        if (! $poblacion) {
            return null;
        }
        $objetivo = (int) ($poblacion->objetivo_cantidad ?? 0);
        if ($objetivo <= 0) {
            return null;
        }
        $cubiertos = (int) ($this->coverage['total_beneficiaries'] ?? 0);
        $pct = (int) round(($cubiertos / $objetivo) * 100);
        if ($pct >= 50) {
            return null;
        }

        return [
            'nivel' => $pct < 25 ? 'rojo' : 'amarillo',
            'pct' => $pct,
        ];
    }

    /** @return array<int, array{municipality: string, drop_pct: int}> */
    private function calcularMunicipiosConDrop(?array $actual, ?array $anterior): array
    {
        if ($actual === null || $anterior === null) {
            return [];
        }
        $anteriorByMun = collect($anterior['by_municipality'] ?? [])
            ->keyBy('municipality')
            ->map(fn ($r) => (int) ($r['count'] ?? 0));
        $actualByMun = collect($actual['by_municipality'] ?? [])
            ->keyBy('municipality')
            ->map(fn ($r) => (int) ($r['count'] ?? 0));

        $drops = [];
        foreach ($anteriorByMun as $mun => $countAnt) {
            if ($countAnt === 0) {
                continue;
            }
            $countAct = (int) ($actualByMun[$mun] ?? 0);
            if ($countAct >= $countAnt) {
                continue;
            }
            $dropPct = (int) round((($countAnt - $countAct) / $countAnt) * 100);
            if ($dropPct < 30) {
                continue;
            }
            $drops[] = [
                'municipality' => (string) $mun,
                'drop_pct' => $dropPct,
            ];
        }

        return $drops;
    }

    public function mapaUrl(): string
    {
        return route('mml.cobertura.mapa', [
            'programa' => $this->programa->id,
            'period' => $this->periodoSeleccionado,
        ]);
    }

    public function render()
    {
        return view('livewire.mml.cobertura-programa');
    }
}
