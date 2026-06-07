<?php

namespace Database\Factories\Evaluation;

use App\Models\Evaluation\EvaluacionExterna;
use App\Models\Evaluation\InformeEvaluacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class InformeEvaluacionFactory extends Factory
{
    protected $model = InformeEvaluacion::class;

    public function definition(): array
    {
        return [
            'evaluacion_externa_id' => EvaluacionExterna::factory(),
            'resumen_ejecutivo' => $this->faker->paragraph(3),
            'metodologia' => $this->faker->paragraph(2),
            'conclusiones' => $this->faker->paragraph(2),
            'fichas' => $this->faker->paragraph(1),
        ];
    }
}
