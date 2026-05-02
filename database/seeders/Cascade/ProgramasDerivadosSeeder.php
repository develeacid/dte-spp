<?php

namespace Database\Seeders\Cascade;

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

        if (! $plan) {
            $this->command->error('No existe un plan activo. Ejecuta PedSeeder primero.');

            return;
        }

        // 1. Programa Sectorial de Desarrollo Económico (Eje 4: temas 4.1, 4.4, 4.5)
        $economico = ProgramaDerivado::updateOrCreate(
            ['ped_plan_id' => $plan->id, 'nombre' => 'Programa Sectorial de Desarrollo Económico'],
            [
                'tipo' => TipoProgramaDerivado::SECTORIAL,
                'descripcion' => 'Programa que orienta las acciones del sector económico para el fortalecimiento productivo del estado.',
            ]
        );
        $this->crearObjetivos($economico, [
            '1' => 'Fortalecer las cadenas productivas estratégicas del estado',
            '2' => 'Impulsar el desarrollo turístico sustentable en las ocho regiones',
            '3' => 'Fomentar la producción agroalimentaria y agroindustrial',
            '4' => 'Promover el acceso a financiamiento para emprendedores y MiPyMEs',
        ]);
        $this->command->info("  {$economico->prefijoClave()}: {$economico->nombre}");

        // 2. Programa Sectorial de Salud (Eje 1: tema 1.9)
        $salud = ProgramaDerivado::updateOrCreate(
            ['ped_plan_id' => $plan->id, 'nombre' => 'Programa Sectorial de Salud'],
            [
                'tipo' => TipoProgramaDerivado::SECTORIAL,
                'descripcion' => 'Programa que orienta las acciones del sector salud para garantizar el acceso efectivo a servicios de calidad.',
            ]
        );
        $this->crearObjetivos($salud, [
            '1' => 'Fortalecer la rectoría y coordinación interinstitucional del sector salud',
            '2' => 'Impulsar el monitoreo y evaluación de indicadores de salud pública',
            '3' => 'Mejorar la capacidad, seguridad y calidad de los servicios de salud',
        ]);
        $this->command->info("  {$salud->prefijoClave()}: {$salud->nombre}");

        // 3. Programa Sectorial de Seguridad Pública (Eje 3: tema 3.1)
        $seguridad = ProgramaDerivado::updateOrCreate(
            ['ped_plan_id' => $plan->id, 'nombre' => 'Programa Sectorial de Seguridad Pública'],
            [
                'tipo' => TipoProgramaDerivado::SECTORIAL,
                'descripcion' => 'Programa que orienta las acciones del sector seguridad para fortalecer la paz y protección ciudadana.',
            ]
        );
        $this->crearObjetivos($seguridad, [
            '1' => 'Fortalecer el equipamiento y capacidades operativas de las corporaciones policiales',
            '2' => 'Impulsar la profesionalización y certificación del personal de seguridad',
            '3' => 'Coordinar la prevención del delito entre instancias estatales y municipales',
        ]);
        $this->command->info("  {$seguridad->prefijoClave()}: {$seguridad->nombre}");

        // Resumen
        $this->command->newLine();
        $this->command->table(
            ['Tipo', 'Programa', 'Objetivos'],
            [
                [
                    'Sectorial',
                    $economico->nombre,
                    $economico->objetivos()->count(),
                ],
                [
                    'Sectorial',
                    $salud->nombre,
                    $salud->objetivos()->count(),
                ],
                [
                    'Sectorial',
                    $seguridad->nombre,
                    $seguridad->objetivos()->count(),
                ],
            ]
        );
    }

    /**
     * Crea objetivos para un programa derivado.
     *
     * @param  array<string, string>  $objetivos  Mapa clave => descripción
     */
    private function crearObjetivos(ProgramaDerivado $programa, array $objetivos): void
    {
        foreach ($objetivos as $clave => $descripcion) {
            ProgramaDerivadoObjetivo::updateOrCreate(
                [
                    'programa_derivado_id' => $programa->id,
                    'clave' => (string) $clave,
                ],
                [
                    'descripcion' => $descripcion,
                ]
            );
        }
    }
}
