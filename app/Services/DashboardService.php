<?php

namespace App\Services;

use App\Enums\EstadoAvance;
use App\Models\Mml\MetaPeriodo;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const TTL = 600; // 10 minutes

    public function getAdminStats(int $teamId): object
    {
        return Cache::remember("dashboard:admin-stats:{$teamId}", self::TTL, function () use ($teamId) {
            $programas = ProgramaPresupuestario::paraTeam($teamId)->count();

            $indicadores = \App\Models\Mml\Indicador::whereHas('mirNivel.programa', fn ($q) => $q->paraTeam($teamId))
                ->where('activo_seguimiento', true)
                ->count();

            $avancePromedio = $this->calcularAvancePromedio($teamId);

            $vencidos = MetaPeriodo::where('fecha_cierre', '<', now())
                ->where('activo', true)
                ->doesntHave('avance')
                ->whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($teamId))
                ->count();

            return (object) compact('programas', 'indicadores', 'avancePromedio', 'vencidos');
        });
    }

    public function getOperadorStats(int $userId, int $teamId): object
    {
        return Cache::remember("dashboard:operador-stats:{$userId}", self::TTL, function () use ($userId) {
            $pendientes = Avance::where('capturado_por', $userId)
                ->where('estado', EstadoAvance::EN_CAPTURA)
                ->count();

            $capturadosMes = Avance::where('capturado_por', $userId)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->whereIn('estado', [EstadoAvance::EN_REVISION, EstadoAvance::APROBADO])
                ->count();

            return (object) compact('pendientes', 'capturadosMes');
        });
    }

    public function getSemaforoDistribution(int $teamId): array
    {
        return Cache::remember("dashboard:semaforo:{$teamId}", self::TTL, function () use ($teamId) {
            $counts = Avance::whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($teamId))
                ->whereNotNull('semaforo_calculado')
                ->select('semaforo_calculado', DB::raw('count(*) as total'))
                ->groupBy('semaforo_calculado')
                ->pluck('total', 'semaforo_calculado')
                ->toArray();

            return [
                'verde' => $counts['verde'] ?? 0,
                'amarillo' => $counts['amarillo'] ?? 0,
                'rojo' => $counts['rojo'] ?? 0,
            ];
        });
    }

    public function getSemaforoUsuario(int $userId): array
    {
        return Cache::remember("dashboard:semaforo-user:{$userId}", self::TTL, function () use ($userId) {
            $counts = Avance::where('capturado_por', $userId)
                ->whereNotNull('semaforo_calculado')
                ->select('semaforo_calculado', DB::raw('count(*) as total'))
                ->groupBy('semaforo_calculado')
                ->pluck('total', 'semaforo_calculado')
                ->toArray();

            return [
                'verde' => $counts['verde'] ?? 0,
                'amarillo' => $counts['amarillo'] ?? 0,
                'rojo' => $counts['rojo'] ?? 0,
            ];
        });
    }

    public function getAvancePorPrograma(int $teamId): Collection
    {
        return Cache::remember("dashboard:avance-programa:{$teamId}", self::TTL, function () use ($teamId) {
            $programas = ProgramaPresupuestario::paraTeam($teamId)
                ->with(['mirNiveles.indicadores' => fn ($q) => $q->where('activo_seguimiento', true)])
                ->get();

            return $programas->map(function ($programa) {
                $indicadores = $programa->mirNiveles->flatMap->indicadores;
                $totalIndicadores = $indicadores->count();

                if ($totalIndicadores === 0) {
                    return null;
                }

                $indicadorIds = $indicadores->pluck('id');

                $avances = Avance::whereIn('indicador_id', $indicadorIds)
                    ->with('metaPeriodo')
                    ->get();

                if ($avances->isEmpty()) {
                    return [
                        'programa' => $programa->clave,
                        'real' => 0,
                        'programado' => 0,
                    ];
                }

                $totalReal = 0;
                $totalMeta = 0;
                foreach ($avances as $avance) {
                    $meta = $avance->metaPeriodo?->meta_periodo ?? 0;
                    if ($meta > 0) {
                        $totalReal += ($avance->resultado / $meta) * 100;
                        $totalMeta += 100;
                    }
                }

                return [
                    'programa' => $programa->clave,
                    'real' => $totalMeta > 0 ? round($totalReal / ($totalMeta / 100), 1) : 0,
                    'programado' => 100,
                ];
            })->filter()->values();
        });
    }

    public function getTendenciaCaptura(int $teamId): Collection
    {
        return Cache::remember("dashboard:tendencia:{$teamId}", self::TTL, function () use ($teamId) {
            $desde = now()->subMonths(5)->startOfMonth();

            $raw = Avance::whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($teamId))
                ->where('created_at', '>=', $desde)
                ->select(
                    DB::raw("to_char(created_at, 'YYYY-MM') as mes"),
                    DB::raw('count(*) as total')
                )
                ->groupBy('mes')
                ->orderBy('mes')
                ->pluck('total', 'mes');

            // Fill missing months with 0
            $result = collect();
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $key = $month->format('Y-m');
                $result->push([
                    'mes' => $month->translatedFormat('M'),
                    'count' => $raw[$key] ?? 0,
                ]);
            }

            return $result;
        });
    }

    private function calcularAvancePromedio(int $teamId): float
    {
        $avances = Avance::whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($teamId))
            ->with('metaPeriodo')
            ->get();

        if ($avances->isEmpty()) {
            return 0;
        }

        $percentages = $avances->map(function ($avance) {
            $meta = $avance->metaPeriodo?->meta_periodo ?? 0;

            return $meta > 0 ? min(($avance->resultado / $meta) * 100, 200) : 0;
        });

        return round($percentages->avg(), 1);
    }
}
