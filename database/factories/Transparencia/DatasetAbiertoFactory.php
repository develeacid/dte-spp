<?php

namespace Database\Factories\Transparencia;

use App\Enums\EstadoDatasetAbierto;
use App\Models\Transparencia\DatasetAbierto;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatasetAbiertoFactory extends Factory
{
    protected $model = DatasetAbierto::class;

    public function definition(): array
    {
        $clave = 'DS-' . str_pad((string) $this->faker->unique()->numberBetween(1, 99), 2, '0', STR_PAD_LEFT);

        return [
            'dataset_clave' => $clave,
            'nombre' => $this->faker->sentence(4),
            'descripcion' => $this->faker->paragraph,
            'sistema_origen' => 'spp',
            'periodo' => '2026-Q' . $this->faker->numberBetween(1, 4),
            'status' => EstadoDatasetAbierto::BORRADOR->value,
            'hash_sha256' => hash('sha256', $this->faker->uuid),
            'ruta_archivo' => 'storage/app/legal/test.pdf',
        ];
    }
}
