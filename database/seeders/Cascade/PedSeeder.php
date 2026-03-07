<?php

namespace Database\Seeders\Cascade;

use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use Illuminate\Database\Seeder;

class PedSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creando estructura PED de prueba...');

        // 1. Crear Plan (activo)
        $plan = PedPlan::updateOrCreate(
            ['nombre' => 'Plan Estatal de Desarrollo 2025-2030'],
            [
                'nivel_gobierno' => 'estatal',
                'periodo_inicio' => 2025,
                'periodo_fin' => 2030,
                'activo' => true,
            ]
        );
        $this->command->info("Plan: {$plan->nombre}");

        // 2. Crear 3 Ejes
        $ejesData = [
            ['numero' => '1', 'nombre' => 'Desarrollo Económico y Empleo'],
            ['numero' => '2', 'nombre' => 'Desarrollo Social y Humano'],
            ['numero' => '3', 'nombre' => 'Gobierno Eficiente y Transparente'],
        ];

        foreach ($ejesData as $ejeData) {
            $eje = PedEje::updateOrCreate(
                ['ped_plan_id' => $plan->id, 'numero' => $ejeData['numero']],
                ['nombre' => $ejeData['nombre']]
            );
            $this->command->info("  Eje {$eje->numero}: {$eje->nombre}");

            // 3. Crear 2 Temas por Eje
            for ($t = 1; $t <= 2; $t++) {
                $temaNumero = $eje->numero . '.' . $t;
                $tema = PedTema::updateOrCreate(
                    ['ped_eje_id' => $eje->id, 'numero' => (string) $t],
                    ['nombre' => "Tema {$temaNumero} del Eje {$eje->numero}"]
                );

                // 4. Crear 2 Objetivos Estratégicos por Tema
                for ($o = 1; $o <= 2; $o++) {
                    $objClave = (string) $o;
                    $objetivo = PedObjetivoEstrategico::updateOrCreate(
                        ['ped_tema_id' => $tema->id, 'clave' => $objClave],
                        ['descripcion' => "Objetivo Estratégico {$temaNumero}.{$o}"]
                    );

                    // 5. Crear 2 Estrategias por Objetivo
                    for ($e = 1; $e <= 2; $e++) {
                        $estClave = (string) $e;
                        $estrategia = PedEstrategia::updateOrCreate(
                            ['ped_objetivo_estrategico_id' => $objetivo->id, 'clave' => $estClave],
                            ['descripcion' => "Estrategia {$temaNumero}.{$o}.{$e}"]
                        );

                        // 6. Crear 2 Líneas de Acción por Estrategia
                        for ($l = 1; $l <= 2; $l++) {
                            $lineaClave = (string) $l;
                            PedLineaAccion::updateOrCreate(
                                ['ped_estrategia_id' => $estrategia->id, 'clave' => $lineaClave],
                                ['descripcion' => "Línea de Acción {$temaNumero}.{$o}.{$e}.{$l}"]
                            );
                        }
                    }
                }
            }
        }

        // Resumen
        $this->command->newLine();
        $this->command->table(
            ['Entidad', 'Cantidad'],
            [
                ['Planes', PedPlan::count()],
                ['Ejes', PedEje::count()],
                ['Temas', PedTema::count()],
                ['Objetivos Estratégicos', PedObjetivoEstrategico::count()],
                ['Estrategias', PedEstrategia::count()],
                ['Líneas de Acción', PedLineaAccion::count()],
            ]
        );
    }
}
