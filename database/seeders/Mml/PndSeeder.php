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

            // Detectar Eje: "## Eje 1: Política y Gobierno"
            if (preg_match('/^## Eje (\d+): (.+)/', $line, $matches)) {
                $ejeNumero = (int) $matches[1];
                $currentEje = PndEje::updateOrCreate(
                    ['numero' => $ejeNumero],
                    ['nombre' => trim($matches[2])]
                );
                $currentObjetivo = null;
                $this->command->info("Eje {$ejeNumero}: {$matches[2]}");
            }

            // Descripción del Eje (línea no-heading después del título)
            elseif ($currentEje && !$currentObjetivo && !preg_match('/^#/', $line) && $line) {
                $currentEje->update(['descripcion' => $line]);
            }

            // Detectar Objetivo: "### Objetivo 1.1" o "### Objetivo 1.1: Título"
            elseif ($currentEje && preg_match('/^### Objetivo (\d+\.\d+)(?::\s*(.+))?/', $line, $matches)) {
                $currentObjetivo = PndObjetivo::updateOrCreate(
                    ['clave' => $matches[1]],
                    [
                        'pnd_eje_id' => $currentEje->id,
                        'descripcion' => isset($matches[2]) ? trim($matches[2]) : null,
                    ]
                );
                $this->command->info("  Objetivo {$matches[1]}");
            }

            // Descripción del Objetivo (línea siguiente si no tenía descripción en el título)
            elseif ($currentObjetivo && !$currentObjetivo->descripcion && !preg_match('/^#/', $line) && $line) {
                $currentObjetivo->update(['descripcion' => $line]);
            }

            // Detectar Estrategia: "#### Estrategia 1.1.1"
            elseif ($currentObjetivo && preg_match('/^#### Estrategia (\d+\.\d+\.\d+)(?::\s*(.+))?/', $line, $matches)) {
                PndEstrategia::updateOrCreate(
                    ['clave' => $matches[1]],
                    [
                        'pnd_objetivo_id' => $currentObjetivo->id,
                        'descripcion' => isset($matches[2]) ? trim($matches[2]) : null,
                    ]
                );
                $this->command->info("    Estrategia {$matches[1]}");
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
