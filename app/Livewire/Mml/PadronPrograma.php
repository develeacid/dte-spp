<?php

namespace App\Livewire\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use App\Services\Padron\PadronSnapshotService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PadronPrograma extends Component
{
    public ProgramaPresupuestario $programa;

    public ?int $componenteSeleccionado = null;

    public string $modoFuente = 'snapshot';

    public bool $modoVivoDisponible = false;

    public ?int $snapshotIdSeleccionado = null;

    public array $componentesDelPrograma = [];

    public array $snapshotsHistoricos = [];

    public array $kpis = [
        'total' => 0,
        'por_genero' => [],
        'por_grupo_edad' => [],
        'por_indigena' => [],
        'por_discapacidad' => [],
        'por_pueblo' => [],
    ];

    public ?string $errorMessage = null;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->authorize('ver_padron');
        $this->programa = $programa;

        if (! $programa->geobase_program_id) {
            return;
        }

        $this->cargarComponentes();

        if ($this->componenteSeleccionado) {
            $this->cargarSnapshots();
            $this->cargarKpis();
        }
    }

    public function seleccionarComponente(int $componenteId): void
    {
        $this->componenteSeleccionado = $componenteId;
        $this->snapshotIdSeleccionado = null;
        $this->cargarSnapshots();
        $this->cargarKpis();
    }

    public function toggleFuente(): void
    {
        if (! $this->modoVivoDisponible) {
            session()->flash(
                'info',
                'Modo "vivo" no disponible en esta versión. Usando snapshots históricos.'
            );

            return;
        }

        $this->modoFuente = $this->modoFuente === 'snapshot' ? 'vivo' : 'snapshot';
        $this->cargarKpis();
    }

    public function seleccionarSnapshot(int $id): void
    {
        $this->snapshotIdSeleccionado = $id;
        $this->cargarKpis();
    }

    public function generarSnapshot(): void
    {
        $this->authorize('generar_snapshot_padron');

        if (! $this->componenteSeleccionado) {
            $this->errorMessage = 'Selecciona un Componente antes de generar el snapshot.';

            return;
        }

        try {
            app(PadronSnapshotService::class)->generar(
                $this->programa,
                $this->componenteSeleccionado,
                auth()->user(),
            );

            $this->cargarSnapshots();
            $this->cargarKpis();
            session()->flash('success', 'Snapshot generado correctamente.');
        } catch (GeoBaseException $e) {
            $this->errorMessage = "Error al generar snapshot: {$e->getMessage()}";
        }
    }

    private function cargarComponentes(): void
    {
        $componentes = MirNivel::query()
            ->where('programa_presupuestario_id', $this->programa->id)
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE)
            ->orderBy('orden')
            ->get(['id', 'orden', 'resumen_narrativo']);

        $this->componentesDelPrograma = $componentes
            ->mapWithKeys(fn ($n) => [
                $n->id => 'C' . $n->orden . ' — ' . str($n->resumen_narrativo)->limit(60),
            ])
            ->toArray();

        if (empty($this->componenteSeleccionado) && ! empty($this->componentesDelPrograma)) {
            $this->componenteSeleccionado = (int) array_key_first($this->componentesDelPrograma);
        }
    }

    private function cargarSnapshots(): void
    {
        try {
            $client = app(GeoBaseClient::class);
            $response = $client->getSnapshots(
                $this->programa->geobase_program_id,
                $this->componenteSeleccionado,
            );

            $this->snapshotsHistoricos = $response['data'] ?? [];

            if (empty($this->snapshotIdSeleccionado) && ! empty($this->snapshotsHistoricos)) {
                $this->snapshotIdSeleccionado = (int) ($this->snapshotsHistoricos[0]['id'] ?? null);
            }
        } catch (GeoBaseException $e) {
            $this->errorMessage = $e->getMessage();
            $this->snapshotsHistoricos = [];
        }
    }

    private function cargarKpis(): void
    {
        if ($this->modoFuente === 'snapshot' && ! $this->snapshotIdSeleccionado) {
            $this->kpis = $this->kpisVacios();

            return;
        }

        $cacheKey = sprintf(
            'padron:%d:%s:%s:%s',
            $this->programa->id,
            $this->componenteSeleccionado ?? 'none',
            $this->modoFuente,
            $this->snapshotIdSeleccionado ?? 'none',
        );

        try {
            $client = app(GeoBaseClient::class);
            $this->kpis = Cache::remember($cacheKey, 60, function () use ($client) {
                $response = $client->getSnapshotKpis($this->snapshotIdSeleccionado);

                return $this->mapearDesagregados($response['data'] ?? []);
            });
        } catch (GeoBaseException $e) {
            $this->errorMessage = $e->getMessage();
            $this->kpis = $this->kpisVacios();
        }
    }

    private function mapearDesagregados(array $snapshot): array
    {
        $desagregados = $snapshot['metadata']['desagregados'] ?? [];

        return [
            'total' => (int) ($snapshot['valor_oficial'] ?? $snapshot['row_count'] ?? 0),
            'por_genero' => $desagregados['por_genero'] ?? [],
            'por_grupo_edad' => $desagregados['por_grupo_edad'] ?? [],
            'por_indigena' => $desagregados['por_indigena'] ?? [],
            'por_discapacidad' => $desagregados['por_discapacidad'] ?? [],
            'por_pueblo' => $desagregados['por_pueblo'] ?? [],
        ];
    }

    private function kpisVacios(): array
    {
        return [
            'total' => 0,
            'por_genero' => [],
            'por_grupo_edad' => [],
            'por_indigena' => [],
            'por_discapacidad' => [],
            'por_pueblo' => [],
        ];
    }

    public function render()
    {
        return view('livewire.mml.padron-programa');
    }
}
