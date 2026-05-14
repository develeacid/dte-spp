<?php

namespace Database\Factories\Portal;

use App\Models\Portal\PubPrograma;
use Illuminate\Database\Eloquent\Factories\Factory;

class PubProgramaFactory extends Factory
{
    protected $model = PubPrograma::class;

    public function definition(): array
    {
        return [
            'ejercicio_fiscal' => 2026,
            'programa_clave' => fake()->unique()->bothify('E###'),
            'programa_nombre' => fake()->sentence(3),
            'unidad_responsable' => fake()->company(),
            'modalidad' => fake()->randomElement(['S', 'U', 'E', 'P']),
            'activo' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (PubPrograma $m) {
            $m->setConnection('pgsql_public');
        });
    }
}
