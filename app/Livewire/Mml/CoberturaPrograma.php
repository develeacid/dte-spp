<?php

namespace App\Livewire\Mml;

use App\Enums\TipoNivelMir;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
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

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->authorize('ver_padron');
        $this->programa = $programa;

        $this->cargarSupuestos();

        if (! $programa->padron_geobase_activo) {
            $this->estado = 'inactivo';

            return;
        }

        try {
            $this->coverage = app(GeoBaseClient::class)->getProgramCoverage($programa->id);
            $this->consultadoAt = now()->format('Y-m-d H:i');
            $this->estado = ((int) ($this->coverage['total_beneficiaries'] ?? 0)) === 0
                ? 'vacio'
                : 'ok';
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

    public function render()
    {
        return view('livewire.mml.cobertura-programa');
    }
}
