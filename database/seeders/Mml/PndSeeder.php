<?php

namespace Database\Seeders\Mml;

use App\Models\PndEje;
use App\Models\PndEstrategia;
use App\Models\PndObjetivo;
use Illuminate\Database\Seeder;

class PndSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('docs/data/pnd-vigente.md');

        if (!file_exists($path)) {
            $this->command->error("Archivo fuente no encontrado: {$path}");
            return;
        }

        $content = file_get_contents($path);
        $lines = explode("\n", $content);

        $currentEje = null;
        $currentObjetivo = null;
        $ejeNumero = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            // Detectar Eje General: "## Eje 1: ..." o Eje Transversal: "## Eje Transversal 1: ..."
            if (preg_match('/^## Eje (?:Transversal )?(\d+): (.+)/', $line, $matches)) {
                $isTransversal = str_contains($line, 'Transversal');
                $ejeNumero = $isTransversal
                    ? (100 + (int) $matches[1])  // T1=101, T2=102, T3=103
                    : (int) $matches[1];
                $currentEje = PndEje::updateOrCreate(
                    ['numero' => $ejeNumero],
                    ['nombre' => trim($matches[2])]
                );
                $currentObjetivo = null;
                $prefix = $isTransversal ? "Eje Transversal {$matches[1]}" : "Eje {$matches[1]}";
                $this->command->info("{$prefix}: {$matches[2]}");
            }

            // Descripción del Eje (línea no-heading después del título)
            elseif ($currentEje && !$currentObjetivo && !preg_match('/^#/', $line) && $line) {
                $currentEje->update(['descripcion' => $line]);
            }

            // Detectar Objetivo: "### Objetivo 1.1" o "### Objetivo T1.1"
            elseif ($currentEje && preg_match('/^### Objetivo (T?\d+\.\d+)/', $line, $matches)) {
                $currentObjetivo = PndObjetivo::updateOrCreate(
                    ['clave' => $matches[1]],
                    [
                        'pnd_eje_id' => $currentEje->id,
                        'descripcion' => null,
                    ]
                );
                $this->command->info("  Objetivo {$matches[1]}");
            }

            // Descripción del Objetivo (línea siguiente si no tenía descripción en el título)
            elseif ($currentObjetivo && !$currentObjetivo->descripcion && !preg_match('/^#/', $line) && $line) {
                $currentObjetivo->update(['descripcion' => $line]);
            }

            // Detectar Estrategia: "#### Estrategia 1.1.1" o "#### Estrategia T1.1.1"
            elseif ($currentObjetivo && preg_match('/^#### Estrategia (T?\d+\.\d+\.\d+)/', $line, $matches)) {
                PndEstrategia::updateOrCreate(
                    ['clave' => $matches[1]],
                    [
                        'pnd_objetivo_id' => $currentObjetivo->id,
                        'descripcion' => null,
                    ]
                );
                $this->command->info("    Estrategia {$matches[1]}");
            }

            // Descripción de Estrategia (línea siguiente)
            elseif (isset($matches[1]) && !preg_match('/^#/', $line) && $line) {
                $lastEstrategia = PndEstrategia::where('clave', $matches[1])->first();
                if ($lastEstrategia && !$lastEstrategia->descripcion) {
                    $lastEstrategia->update(['descripcion' => $line]);
                }
            }
        }

        $this->command->newLine();
        $this->command->table(
            ['Entidad', 'Cantidad'],
            [
                ['Ejes', PndEje::count()],
                ['Objetivos', PndObjetivo::count()],
                ['Estrategias', PndEstrategia::count()],
            ]
        );
    }
}
