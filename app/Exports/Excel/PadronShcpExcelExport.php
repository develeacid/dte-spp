<?php

namespace App\Exports\Excel;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PadronShcpExcelExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Clave Programa',
            'CURP',
            'Nombre Completo',
            'Sexo',
            'Fecha Nacimiento',
            'Municipio',
            'Monto',
            'Tipo Apoyo',
        ];
    }

    public function map($row): array
    {
        return [
            $row['clave_programa'] ?? '',
            $row['curp'] ?? '',
            $row['nombre_completo'] ?? '',
            $row['sexo'] ?? '',
            $row['fecha_nacimiento'] ?? '',
            $row['municipio_clave'] ?? '',
            $row['monto'] ?? 0,
            $row['tipo_apoyo'] ?? '',
        ];
    }
}
