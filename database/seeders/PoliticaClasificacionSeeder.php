<?php

namespace Database\Seeders;

use App\Enums\EstadoDatasetAbierto;
use App\Models\Transparencia\DatasetAbierto;
use Illuminate\Database\Seeder;
use RuntimeException;

class PoliticaClasificacionSeeder extends Seeder
{
    private const RUTA_MARKDOWN = 'docs/legal/clasificacion-informacion.md';

    public function run(): void
    {
        $rutaAbsoluta = base_path(self::RUTA_MARKDOWN);

        if (! is_file($rutaAbsoluta)) {
            throw new RuntimeException(
                "El archivo del acuerdo no existe: {$rutaAbsoluta}\n".
                'Cree primero la plantilla en '.self::RUTA_MARKDOWN
            );
        }

        DatasetAbierto::updateOrCreate(
            ['dataset_clave' => 'DS-00', 'periodo' => null],
            [
                'nombre' => 'Política institucional de clasificación de información',
                'descripcion' => 'Acuerdo institucional que define los niveles de clasificación '.
                    '(Público / Reservado / Confidencial), criterios de anonimización (k≥5), '.
                    'vigencia de reserva y designación del Responsable de Datos Abiertos (RDA). '.
                    'Prerrequisito de todos los demás datasets.',
                'sistema_origen' => 'spp',
                'status' => EstadoDatasetAbierto::BORRADOR->value,
                'hash_sha256' => hash_file('sha256', $rutaAbsoluta),
                'ruta_archivo' => self::RUTA_MARKDOWN,
            ]
        );
    }
}
