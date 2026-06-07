<?php

namespace Database\Factories\Evaluation;

use App\Models\Evaluation\Hallazgo;
use App\Models\Evaluation\Recomendacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecomendacionFactory extends Factory
{
    protected $model = Recomendacion::class;

    public function definition(): array
    {
        return [
            'hallazgo_id' => Hallazgo::factory(),
            'descripcion' => $this->faker->paragraph(2),
            'prioridad' => $this->faker->randomElement(['baja', 'media', 'alta']),
        ];
    }
}
