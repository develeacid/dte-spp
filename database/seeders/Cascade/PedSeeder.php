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
        $path = base_path('docs/data/ped-oaxaca-2022-2028.md');

        if (!file_exists($path)) {
            $this->command->error("Archivo fuente no encontrado: {$path}");
            return;
        }

        $content = file_get_contents($path);
        $lines = explode("\n", $content);

        $plan = null;
        $currentEje = null;
        $currentTema = null;
        $currentObjetivo = null;
        $currentEstrategia = null;

        foreach ($lines as $line) {
            $line = trim($line);

            // Plan title (H1): "# Plan Estatal de Desarrollo de Oaxaca 2022-2028"
            if (preg_match('/^# (.+)/', $line, $matches)) {
                $planTitle = trim($matches[1]);
                $plan = PedPlan::updateOrCreate(
                    ['nombre' => $planTitle],
                    [
                        'nivel_gobierno' => 'estatal',
                        'periodo_inicio' => 2022,
                        'periodo_fin' => 2028,
                        'activo' => true,
                    ]
                );
                $this->command->info("Plan: {$plan->nombre}");
            }

            // Eje: "## Eje 1: Estado de Bienestar..."
            elseif ($plan && preg_match('/^## Eje (\d+): (.+)/', $line, $matches)) {
                $currentEje = PedEje::updateOrCreate(
                    ['ped_plan_id' => $plan->id, 'numero' => $matches[1]],
                    ['nombre' => trim($matches[2])]
                );
                $currentTema = null;
                $currentObjetivo = null;
                $currentEstrategia = null;
                $this->command->info("  Eje {$matches[1]}: {$matches[2]}");
            }

            // Tema: "### Tema 1.9: Salud"
            elseif ($currentEje && preg_match('/^### Tema ([\d.]+): (.+)/', $line, $matches)) {
                $fullClave = $matches[1]; // e.g. "1.9"
                $parts = explode('.', $fullClave);
                $temaNumero = end($parts); // local number within eje, e.g. "9"

                $currentTema = PedTema::updateOrCreate(
                    ['ped_eje_id' => $currentEje->id, 'numero' => $temaNumero],
                    ['nombre' => trim($matches[2])]
                );
                $currentObjetivo = null;
                $currentEstrategia = null;
                $this->command->info("    Tema {$fullClave}: {$matches[2]}");
            }

            // Objetivo Estratégico: "#### Objetivo 1.9: Consolidar..."
            elseif ($currentTema && preg_match('/^#### Objetivo ([\d.]+): (.+)/', $line, $matches)) {
                $clave = $matches[1]; // full clave, e.g. "1.9"

                $currentObjetivo = PedObjetivoEstrategico::updateOrCreate(
                    ['ped_tema_id' => $currentTema->id, 'clave' => $clave],
                    ['descripcion' => trim($matches[2])]
                );
                $currentEstrategia = null;
                $this->command->info("      Objetivo {$clave}: {$matches[2]}");
            }

            // Estrategia: "##### Estrategia 1.9.1: Fortalecer..."
            elseif ($currentObjetivo && preg_match('/^##### Estrategia ([\d.]+): (.+)/', $line, $matches)) {
                $fullClave = $matches[1]; // e.g. "1.9.1"
                $parts = explode('.', $fullClave);
                $localClave = end($parts); // local number within objective, e.g. "1"

                $currentEstrategia = PedEstrategia::updateOrCreate(
                    ['ped_objetivo_estrategico_id' => $currentObjetivo->id, 'clave' => $localClave],
                    ['descripcion' => trim($matches[2])]
                );
                $this->command->info("        Estrategia {$fullClave}: {$matches[2]}");
            }

            // Línea de Acción: "- **1.9.1.1** Coordinar..."
            elseif ($currentEstrategia && preg_match('/^- \*\*([\d.]+)\*\* (.+)/', $line, $matches)) {
                $fullClave = $matches[1]; // e.g. "1.9.1.1"
                $parts = explode('.', $fullClave);
                $localClave = end($parts); // local number within strategy, e.g. "1"

                PedLineaAccion::updateOrCreate(
                    ['ped_estrategia_id' => $currentEstrategia->id, 'clave' => $localClave],
                    ['descripcion' => trim($matches[2])]
                );
                $this->command->info("          LA {$fullClave}: {$matches[2]}");
            }
        }

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
