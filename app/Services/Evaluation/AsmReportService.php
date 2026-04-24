<?php

namespace App\Services\Evaluation;

use App\Enums\SemaforoAsm;
use App\Enums\StatusAsm;
use App\Models\Evaluation\Asm;
use Illuminate\Support\Collection;

class AsmReportService
{
    public function __construct(private readonly array $filters = [])
    {
    }

    public function rows(): Collection
    {
        $query = Asm::query()
            ->with(['programa:id,clave,nombre', 'responsable:id,name'])
            ->when(
                $this->filters['programa_id'] ?? null,
                fn ($q, $id) => $q->where('programa_presupuestario_id', $id),
            )
            ->orderBy('programa_presupuestario_id')
            ->orderBy('fecha_compromiso');

        return $query->get()->values();
    }

    public function totals(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            'cumplidos' => $rows->where('status', StatusAsm::CUMPLIDO)->count(),
            'en_proceso' => $rows->where('status', StatusAsm::EN_PROCESO)->count(),
            'pendientes' => $rows->where('status', StatusAsm::PENDIENTE)->count(),
            'vencidos' => $rows->filter(fn ($a) => $a->semaforo === SemaforoAsm::VENCIDO)->count(),
        ];
    }
}
