<?php

namespace Database\Factories\Evaluation;

use App\Enums\EstadoEvaluacionExterna;
use App\Enums\TipoEvaluacionExterna;
use App\Models\Evaluation\EvaluacionExterna;
use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluacionExternaFactory extends Factory
{
    protected $model = EvaluacionExterna::class;

    public function definition(): array
    {
        $inicio = $this->faker->dateTimeBetween('-1 year', '-2 months');

        return [
            'programa_presupuestario_id' => ProgramaPresupuestario::factory(),
            'ejercicio_fiscal' => $this->faker->numberBetween(2023, 2026),
            'tipo' => $this->faker->randomElement(TipoEvaluacionExterna::cases()),
            'evaluador_externo' => $this->faker->company(),
            'fecha_inicio' => $inicio,
            'fecha_fin' => $this->faker->dateTimeBetween($inicio, 'now'),
            'estado' => $this->faker->randomElement(EstadoEvaluacionExterna::cases()),
            'evaluacion_programa_id' => null,
        ];
    }
}
