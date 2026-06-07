<?php

namespace Database\Factories\Evaluation;

use App\Models\Evaluation\Hallazgo;
use App\Models\Evaluation\InformeEvaluacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class HallazgoFactory extends Factory
{
    protected $model = Hallazgo::class;

    public function definition(): array
    {
        return [
            'informe_evaluacion_id' => InformeEvaluacion::factory(),
            'descripcion' => $this->faker->paragraph(2),
            'evidencia_url' => $this->faker->optional()->url(),
            'severidad' => $this->faker->randomElement(['baja', 'media', 'alta']),
        ];
    }
}
