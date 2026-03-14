<?php

namespace App\Exports\Excel;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CuentaPublicaExcelExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private array $datos,
        private Collection $resumenEjes,
        private int $ejercicio,
    ) {}

    public function sheets(): array
    {
        return [
            'Resumen Ejecutivo' => new CuentaPublicaResumenSheet($this->datos, $this->ejercicio),
            'Detalle Partidas' => new CuentaPublicaDetalleSheet($this->datos, $this->ejercicio),
            'Alertas' => new CuentaPublicaAlertasSheet($this->datos, $this->ejercicio),
            'Resumen por Eje PED' => new CuentaPublicaEjesSheet($this->resumenEjes, $this->ejercicio),
        ];
    }
}
