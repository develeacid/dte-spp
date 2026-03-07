<?php

namespace Database\Seeders;

use App\Enums\TipoProgramaDerivado;
use App\Models\PedPlan;
use App\Models\ProgramaDerivado;
use App\Models\ProgramaDerivadoObjetivo;
use Illuminate\Database\Seeder;

class ProgramasDerivadosSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creando programas derivados de prueba...');

        // Obtener plan activo
        $plan = PedPlan::where('activo', true)->first();

        if (!$plan) {
            $this->command->error('No existe un plan activo. Ejecuta PedSeeder primero.');
            return;
        }

        // 1. Programa Sectorial
        $sectorial = ProgramaDerivado::updateOrCreate(
            ['ped_plan_id' => $plan->id, 'nombre' => 'Programa Sectorial de Educación'],
            [
                'tipo' => TipoProgramaDerivado::SECTORIAL,
                'descripcion' => 'Programa que orienta las acciones del sector educativo estatal.',
            ]
        );
        $this->crearObjetivos($sectorial, 4);
        $this->command->info("  {$sectorial->prefijoClave()}: {$sectorial->nombre}");

        // 2. Programa Especial
        $especial = ProgramaDerivado::updateOrCreate(
            ['ped_plan_id' => $plan->id, 'nombre' => 'Programa Especial de Cambio Climático'],
            [
                'tipo' => TipoProgramaDerivado::ESPECIAL,
                'descripcion' => 'Programa para atender los efectos del cambio climático en la entidad.',
            ]
        );
        $this->crearObjetivos($especial, 3);
        $this->command->info("  {$especial->prefijoClave()}: {$especial->nombre}");

        // 3. Programa Institucional
        $institucional = ProgramaDerivado::updateOrCreate(
            ['ped_plan_id' => $plan->id, 'nombre' => 'Programa Institucional de Modernización Administrativa'],
            [
                'tipo' => TipoProgramaDerivado::INSTITUCIONAL,
                'descripcion' => 'Programa para fortalecer la gestión pública institucional.',
            ]
        );
        $this->crearObjetivos($institucional, 3);
        $this->command->info("  {$institucional->prefijoClave()}: {$institucional->nombre}");

        // Resumen
        $this->command->newLine();
        $this->command->table(
            ['Tipo', 'Programa', 'Objetivos'],
            [
                [
                    'Sectorial',
                    $sectorial->nombre,
                    $sectorial->objetivos()->count()
                ],
                [
                    'Especial',
                    $especial->nombre,
                    $especial->objetivos()->count()
                ],
                [
                    'Institucional',
                    $institucional->nombre,
                    $institucional->objetivos()->count()
                ],
            ]
        );
    }

    /**
     * Crea objetivos para un programa derivado.
     */
    private function crearObjetivos(ProgramaDerivado $programa, int $cantidad): void
    {
        for ($i = 1; $i <= $cantidad; $i++) {
            ProgramaDerivadoObjetivo::updateOrCreate(
                [
                    'programa_derivado_id' => $programa->id,
                    'clave' => (string) $i
                ],
                [
                    'descripcion' => "Objetivo {$i} del {$programa->nombre}"
                ]
            );
        }
    }
}
