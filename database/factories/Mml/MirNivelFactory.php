<?php

namespace Database\Factories\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MirNivel>
 */
class MirNivelFactory extends Factory
{
    protected $model = MirNivel::class;

    public function definition(): array
    {
        // tipo_nivel por defecto COMPONENTE: Task 4 agregará UNIQUE parcial
        // sobre FIN/PROPOSITO, así que varios niveles por programa colisionarían
        // si el default fuera FIN/PROPOSITO.
        // resumen_narrativo poblado siempre: Task 2 lo hace NOT NULL.
        return [
            'programa_presupuestario_id' => ProgramaPresupuestario::factory(),
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'componente_id' => null,
            'resumen_narrativo' => $this->faker->sentence(8),
            'supuestos' => $this->faker->sentence(6),
            'orden' => 1,
        ];
    }
}
