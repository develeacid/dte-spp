<?php

namespace App\Livewire\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use App\Services\Padron\PadronProvisioningService;
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
        'calidad' => null,
    ];

    public ?string $errorMessage = null;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->authorize('ver_padron');
        $this->programa = $programa;

        if (! $programa->padron_geobase_activo) {
            return;
        }

        $this->cargarComponentes();

        if ($this->componenteSeleccionado) {
            $this->modoVivoDisponible = true;
            $this->cargarSnapshots();
            $this->cargarKpis();
        }
    }

    public function seleccionarComponente(int $componenteId): void
    {
        $this->componenteSeleccionado = $componenteId;
        $this->modoVivoDisponible = true;
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

    public function activarPadron(): void
    {
        $this->authorize('generar_snapshot_padron');

        if ($this->programa->padron_geobase_activo) {
            session()->flash('info', 'Este programa ya tiene padrón activo en GeoBase.');

            return;
        }

        $sinComponentes = $this->programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE)
            ->doesntExist();

        if ($sinComponentes) {
            $this->errorMessage = 'Define al menos un Componente en la MIR antes de activar el padrón.';

            return;
        }

        try {
            $result = app(PadronProvisioningService::class)->register($this->programa);

            $this->programa->refresh();
            $this->cargarComponentes();
            if ($this->componenteSeleccionado) {
                $this->cargarSnapshots();
                $this->cargarKpis();
            }

            session()->flash(
                'success',
                "Padrón activado en GeoBase. {$result['componentes_registrados']} componente(s) registrado(s)."
            );
        } catch (GeoBaseException $e) {
            $this->errorMessage = "Error al activar padrón: {$e->getMessage()}";
        }
    }

    public function desactivarPadron(): void
    {
        $this->authorize('generar_snapshot_padron');

        if (! $this->programa->padron_geobase_activo) {
            session()->flash('info', 'El padrón ya está desactivado en GeoBase.');

            return;
        }

        try {
            $result = app(PadronProvisioningService::class)->deactivate($this->programa);

            $this->programa->refresh();

            session()->flash(
                'success',
                "Padrón desactivado en GeoBase. {$result['componentes_desactivados']} componente(s) marcado(s) inactivo(s)."
            );
        } catch (GeoBaseException $e) {
            $this->errorMessage = "Error al desactivar padrón: {$e->getMessage()}";
        }
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
                $n->id => 'C'.$n->orden.' — '.str($n->resumen_narrativo)->limit(60),
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
                $this->programa->id,
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
            // Vivo mode hits /components/{id}/coverage and exposes the live
            // beneficiary count without desagregados — those only exist in
            // captured snapshots. Snapshot mode keeps the historic shape.
            $this->kpis = Cache::remember(
                $cacheKey,
                $this->modoFuente === 'vivo' ? 30 : 60,
                fn () => $this->modoFuente === 'vivo'
                    ? $this->mapearVivo($client->getComponentCoverage($this->componenteSeleccionado))
                    : $this->mapearDesagregados($client->getSnapshotKpis($this->snapshotIdSeleccionado)['data'] ?? []),
            );
        } catch (GeoBaseException $e) {
            $this->errorMessage = $e->getMessage();
            $this->kpis = $this->kpisVacios();
        }
    }

    private function mapearVivo(array $coverage): array
    {
        $q = $coverage['quality'] ?? null;

        return [
            'total' => (int) ($coverage['total_beneficiaries'] ?? 0),
            'por_genero' => [],
            'por_grupo_edad' => [],
            'por_indigena' => [],
            'por_discapacidad' => [],
            'por_pueblo' => [],
            'calidad' => $q === null ? null : [
                'completos' => (int) ($q['complete_records'] ?? 0),
                'completos_pct' => (float) ($q['complete_pct'] ?? 0),
                'verificados' => (int) ($q['verified_enrollments'] ?? 0),
                'verificados_pct' => (float) ($q['verified_pct'] ?? 0),
                'total_beneficiarios' => (int) ($q['total_beneficiaries'] ?? 0),
                'total_enrollments' => (int) ($q['total_enrollments'] ?? 0),
            ],
        ];
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
            'calidad' => null,
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
            'calidad' => null,
        ];
    }

    public function render()
    {
        return view('livewire.mml.padron-programa');
    }
}
