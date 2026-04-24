<?php

namespace Database\Factories\Presupuesto;

use App\Models\Presupuesto\AvanceFinanciero;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvanceFinanciero>
 */
class AvanceFinancieroFactory extends Factory
{
    protected $model = AvanceFinanciero::class;

    public function definition(): array
    {
        return [
            'partida_presupuestal_id' => PartidaPresupuestal::factory(),
            'trimestre' => 1,
            'monto_comprometido' => 0,
            'monto_devengado' => 0,
            'monto_pagado' => 0,
            'registrado_por' => User::factory(),
            'observaciones' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (AvanceFinanciero $avance): void {
            $pagado = (float) $avance->monto_pagado;
            $devengado = (float) $avance->monto_devengado;
            $comprometido = (float) $avance->monto_comprometido;

            if ($devengado < $pagado) {
                $avance->monto_devengado = $pagado;
                $devengado = $pagado;
            }
            if ($comprometido < $devengado) {
                $avance->monto_comprometido = $devengado;
            }
        });
    }
}
