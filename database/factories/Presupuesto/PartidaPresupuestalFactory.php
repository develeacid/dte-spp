<?php

namespace Database\Factories\Presupuesto;

use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartidaPresupuestal>
 */
class PartidaPresupuestalFactory extends Factory
{
    protected $model = PartidaPresupuestal::class;

    public function definition(): array
    {
        return [
            'programa_presupuestario_id' => ProgramaPresupuestario::factory(),
            'clave_partida' => (string) $this->faker->unique()->numberBetween(1101, 9999),
            'descripcion' => $this->faker->sentence(3),
            'monto_aprobado' => $this->faker->numberBetween(50_000, 1_000_000),
            'monto_modificado' => null,
            'ejercicio_fiscal' => 2026,
            'team_id' => Team::factory(),
            'registrado_por' => User::factory(),
        ];
    }
}
