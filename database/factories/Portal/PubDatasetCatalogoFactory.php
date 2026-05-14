<?php

namespace Database\Factories\Portal;

use App\Models\Portal\PubDatasetCatalogo;
use Illuminate\Database\Eloquent\Factories\Factory;

class PubDatasetCatalogoFactory extends Factory
{
    protected $model = PubDatasetCatalogo::class;

    public function definition(): array
    {
        return [
            'codigo' => 'DS-'.fake()->unique()->numerify('##'),
            'titulo' => fake()->sentence(3),
            'descripcion' => fake()->paragraph(),
            'fecha_publicacion' => now()->toDateString(),
            'frecuencia_actualizacion' => 'Anual',
            'total_registros' => fake()->numberBetween(0, 1000),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (PubDatasetCatalogo $m) {
            $m->setConnection('pgsql_public');
        });
    }
}
