<?php

namespace Database\Factories\Evaluation;

use App\Enums\StatusAsm;
use App\Enums\TipoAccionAsm;
use App\Enums\TipoPlazoAsm;
use App\Models\Evaluation\Asm;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AsmFactory extends Factory
{
    protected $model = Asm::class;

    public function definition(): array
    {
        return [
            'programa_presupuestario_id' => ProgramaPresupuestario::factory(),
            'evaluacion_id' => null,
            'descripcion_aspecto' => $this->faker->paragraph(3),
            'accion_mejora' => $this->faker->paragraph(2),
            'tipo_plazo' => $this->faker->randomElement(TipoPlazoAsm::cases()),
            'tipo_accion' => $this->faker->randomElement(TipoAccionAsm::cases()),
            'responsable_id' => User::factory(),
            'area_responsable' => $this->faker->jobTitle(),
            'fecha_compromiso' => $this->faker->dateTimeBetween('-60 days', '+180 days'),
            'fecha_cumplimiento' => null,
            'porcentaje_avance' => $this->faker->numberBetween(0, 80),
            'observacion_ultimo_avance' => null,
            'status' => StatusAsm::PENDIENTE,
            'evidencia_url' => null,
        ];
    }

    public function cumplido(): static
    {
        return $this->state(fn () => [
            'status' => StatusAsm::CUMPLIDO,
            'porcentaje_avance' => 100,
            'fecha_cumplimiento' => now(),
        ]);
    }

    public function vencido(): static
    {
        return $this->state(fn () => [
            'status' => StatusAsm::EN_PROCESO,
            'fecha_compromiso' => now()->subDays(15),
            'porcentaje_avance' => 40,
        ]);
    }
}
