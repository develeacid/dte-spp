<?php

namespace Database\Factories\Transparencia;

use App\Models\Transparencia\DatasetAbierto;
use App\Models\Transparencia\TransparenciaPublicacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransparenciaPublicacionFactory extends Factory
{
    protected $model = TransparenciaPublicacion::class;

    public function definition(): array
    {
        return [
            'dataset_abierto_id' => DatasetAbierto::factory(),
            'dataset_clave' => $this->faker->randomElement(['DS-01', 'DS-02', 'DS-03', 'DS-04', 'DS-05', 'DS-G04']),
            'action' => 'publish',
            'success' => true,
            'payload_hash' => hash('sha256', $this->faker->sentence()),
            'registros_count' => $this->faker->numberBetween(1, 1000),
            'publicado_por_user_id' => User::factory(),
            'error_message' => null,
            'publicado_at' => now(),
        ];
    }
}
