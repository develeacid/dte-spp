<?php

namespace Database\Factories\Mml;

use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\SentidoIndicador;
use App\Enums\TipoIndicador;
use App\Models\CatalogoUnidadMedida;
use App\Models\Mml\Indicador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Indicador>
 */
class IndicadorFactory extends Factory
{
    protected $model = Indicador::class;

    public function definition(): array
    {
        // Task 2 hará sentido, formula_texto y unidad_medida_id NOT NULL:
        // el factory debe poblarlos siempre.
        $unidad = CatalogoUnidadMedida::firstOrCreate(
            ['clave' => 'PCT'],
            ['nombre' => 'Porcentaje']
        );

        return [
            'mir_nivel_id' => null,
            'nombre' => 'Indicador '.$this->faker->unique()->numberBetween(1, 99999),
            'formula_texto' => '(A / B) * 100',
            'tipo' => $this->faker->randomElement(TipoIndicador::cases())->value,
            'dimension' => $this->faker->randomElement(DimensionIndicador::cases())->value,
            'frecuencia' => FrecuenciaMedicion::TRIMESTRAL->value,
            'sentido' => $this->faker->randomElement(SentidoIndicador::cases())->value,
            'linea_base' => $this->faker->randomFloat(2, 0, 50),
            'meta' => $this->faker->randomFloat(2, 50, 100),
            'unidad_medida_id' => $unidad->id,
            'orden' => 1,
            'activo_seguimiento' => true,
        ];
    }
}
