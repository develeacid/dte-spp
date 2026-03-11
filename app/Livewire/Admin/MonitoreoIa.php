<?php

namespace App\Livewire\Admin;

use App\Models\LlmBudget;
use App\Models\LlmLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class MonitoreoIa extends Component
{
    #[Url]
    public string $periodo = 'month';

    #[Url]
    public string $fechaDesde = '';

    #[Url]
    public string $fechaHasta = '';

    public ?array $resultadoConexion = null;

    public function mount(): void
    {
        if (empty($this->fechaDesde) || empty($this->fechaHasta)) {
            $this->applyPeriodo();
        }
    }

    public function updatedPeriodo(): void
    {
        $this->applyPeriodo();
    }

    private function applyPeriodo(): void
    {
        $this->fechaHasta = now()->toDateString();

        $this->fechaDesde = match ($this->periodo) {
            'day' => now()->toDateString(),
            'week' => now()->subWeek()->toDateString(),
            'month' => now()->startOfMonth()->toDateString(),
            default => now()->startOfMonth()->toDateString(),
        };
    }

    private function baseQuery()
    {
        return LlmLog::query()
            ->whereBetween('llm_logs.created_at', [
                Carbon::parse($this->fechaDesde)->startOfDay(),
                Carbon::parse($this->fechaHasta)->endOfDay(),
            ]);
    }

    public function getMetricas(): array
    {
        $query = $this->baseQuery();

        $stats = $query->selectRaw('
            COUNT(*) as total_calls,
            COALESCE(SUM(total_tokens), 0) as total_tokens,
            COALESCE(SUM(cost_usd), 0) as total_cost,
            COALESCE(AVG(CASE WHEN status = \'success\' THEN duration_ms END), 0) as avg_duration,
            COALESCE(SUM(CASE WHEN status = \'error\' THEN 1 ELSE 0 END), 0) as error_count
        ')->first();

        $totalCalls = (int) $stats->total_calls;
        $errorRate = $totalCalls > 0
            ? round(((int) $stats->error_count / $totalCalls) * 100, 1)
            : 0;

        return [
            'total_calls' => $totalCalls,
            'total_tokens' => (int) $stats->total_tokens,
            'total_cost' => round((float) $stats->total_cost, 4),
            'avg_duration' => round((float) $stats->avg_duration),
            'error_rate' => $errorRate,
        ];
    }

    public function getUsoPorTipo(): array
    {
        return $this->baseQuery()
            ->select('method')
            ->selectRaw('COUNT(*) as calls')
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as tokens')
            ->selectRaw('COALESCE(SUM(cost_usd), 0) as cost')
            ->groupBy('method')
            ->orderByDesc('calls')
            ->get()
            ->toArray();
    }

    public function getUsoPorUsuario(): array
    {
        return $this->baseQuery()
            ->join('users', 'llm_logs.user_id', '=', 'users.id')
            ->select('users.name')
            ->selectRaw('COUNT(*) as calls')
            ->selectRaw('COALESCE(SUM(llm_logs.total_tokens), 0) as tokens')
            ->selectRaw('COALESCE(SUM(llm_logs.cost_usd), 0) as cost')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('calls')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function getUsoPorUr(): array
    {
        return $this->baseQuery()
            ->join('users', 'llm_logs.user_id', '=', 'users.id')
            ->join('teams', 'users.current_team_id', '=', 'teams.id')
            ->select('teams.name as team_name')
            ->selectRaw('COUNT(*) as calls')
            ->selectRaw('COALESCE(SUM(llm_logs.total_tokens), 0) as tokens')
            ->selectRaw('COALESCE(SUM(llm_logs.cost_usd), 0) as cost')
            ->groupBy('teams.id', 'teams.name')
            ->orderByDesc('calls')
            ->get()
            ->toArray();
    }

    public function getTendencia(): array
    {
        $groupExpr = $this->periodo === 'month'
            ? "TO_CHAR(created_at, 'YYYY-MM-DD')"
            : "TO_CHAR(created_at, 'YYYY-MM-DD')";

        return $this->baseQuery()
            ->selectRaw("{$groupExpr} as fecha")
            ->selectRaw('COUNT(*) as calls')
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as tokens')
            ->selectRaw('COALESCE(SUM(cost_usd), 0) as cost')
            ->groupByRaw($groupExpr)
            ->orderByRaw("{$groupExpr} ASC")
            ->get()
            ->toArray();
    }

    public function getPresupuestos(): array
    {
        $month = now()->startOfMonth()->toDateString();

        return LlmBudget::forMonth($month)
            ->get()
            ->map(fn (LlmBudget $b) => [
                'id' => $b->id,
                'scope' => $b->scope,
                'scope_id' => $b->scope_id,
                'budget_usd' => (float) $b->budget_usd,
                'spent_usd' => (float) $b->spent_usd,
                'percent' => $b->percentUsed(),
                'threshold' => (float) $b->alert_threshold * 100,
                'over_threshold' => $b->isOverThreshold(),
                'alerted_at' => $b->alerted_at?->format('Y-m-d H:i'),
            ])
            ->toArray();
    }

    public function getAlertas(): array
    {
        $month = now()->startOfMonth()->toDateString();

        return LlmBudget::forMonth($month)
            ->get()
            ->filter(fn (LlmBudget $b) => $b->isOverThreshold())
            ->map(fn (LlmBudget $b) => [
                'scope' => $b->scope,
                'scope_id' => $b->scope_id,
                'percent' => $b->percentUsed(),
                'spent_usd' => (float) $b->spent_usd,
                'budget_usd' => (float) $b->budget_usd,
            ])
            ->values()
            ->toArray();
    }

    public function probarConexion(): void
    {
        abort_unless(auth()->user()->can('administrar_usuarios'), 403);

        $apiKey  = config('services.embedding.api_key', '');
        $apiUrl  = config('services.embedding.url', 'https://api.openai.com/v1/embeddings');
        $model   = config('services.embedding.model', 'text-embedding-ada-002');

        $keyPreview = strlen($apiKey) >= 6
            ? '...' . substr($apiKey, -6)
            : '(no configurada)';

        $inicio = microtime(true);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->post($apiUrl, [
                    'model' => $model,
                    'input' => 'test',
                ]);

            $latencia = (int) round((microtime(true) - $inicio) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                $embedding = $data['data'][0]['embedding'] ?? [];

                $this->resultadoConexion = [
                    'estado'          => 'ok',
                    'modelo'          => $data['model'] ?? $model,
                    'dimensiones'     => count($embedding),
                    'latencia_ms'     => $latencia,
                    'tokens_usados'   => $data['usage']['total_tokens'] ?? 0,
                    'costo_usd'       => round(($data['usage']['total_tokens'] ?? 0) * 0.0000001, 8),
                    'api_key_preview' => $keyPreview,
                    'url'             => $apiUrl,
                ];
            } else {
                $error = $response->json('error.message') ?? $response->body();
                $this->resultadoConexion = [
                    'estado'          => 'error',
                    'latencia_ms'     => $latencia,
                    'api_key_preview' => $keyPreview,
                    'url'             => $apiUrl,
                    'http_status'     => $response->status(),
                    'mensaje_error'   => $error,
                ];
            }
        } catch (\Throwable $e) {
            $latencia = (int) round((microtime(true) - $inicio) * 1000);
            $this->resultadoConexion = [
                'estado'          => 'error',
                'latencia_ms'     => $latencia,
                'api_key_preview' => $keyPreview,
                'url'             => $apiUrl,
                'mensaje_error'   => $e->getMessage(),
            ];
        }
    }

    public function render()
    {
        return view('livewire.admin.monitoreo-ia', [
            'metricas' => $this->getMetricas(),
            'usoPorTipo' => $this->getUsoPorTipo(),
            'usoPorUsuario' => $this->getUsoPorUsuario(),
            'usoPorUr' => $this->getUsoPorUr(),
            'tendencia' => $this->getTendencia(),
            'presupuestos' => $this->getPresupuestos(),
            'alertas' => $this->getAlertas(),
        ]);
    }
}
