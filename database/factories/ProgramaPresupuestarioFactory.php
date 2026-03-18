<?php

namespace Database\Factories;

use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramaPresupuestarioFactory extends Factory
{
    protected $model = ProgramaPresupuestario::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->sentence(3),
            'clave' => fake()->unique()->bothify('PP-####'),
            'ejercicio_fiscal' => 2026,
            'origen' => 'nuevo',
            'estado' => 'borrador',
        ];
    }
}
