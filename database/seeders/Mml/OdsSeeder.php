<?php

namespace Database\Seeders\Mml;

use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use Illuminate\Database\Seeder;

class OdsSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('docs/data/ods-agenda-2030.md');

        if (!file_exists($path)) {
            $this->command->error("Archivo fuente no encontrado: {$path}");
            return;
        }

        $content = file_get_contents($path);
        $lines = explode("\n", $content);

        $currentObjetivo = null;

        foreach ($lines as $line) {
            // Detectar ODS: "## ODS 1: Fin de la Pobreza"
            if (preg_match('/^## ODS (\d+): (.+)/', $line, $matches)) {
                $numeroObjetivo = (int) $matches[1];
                $nombre = trim($matches[2]);

                $currentObjetivo = OdsObjetivo::updateOrCreate(
                    ['numero' => $numeroObjetivo],
                    ['nombre' => $nombre]
                );

                $this->command->info("ODS {$numeroObjetivo}: {$nombre}");
            }

            // Detectar descripción del ODS (línea siguiente al título, no vacía, no encabezado, no meta)
            elseif ($currentObjetivo && !str_starts_with($line, '#') && !str_starts_with($line, '-') && trim($line)) {
                $currentObjetivo->update(['descripcion' => trim($line)]);
            }

            // Detectar Meta: "- **1.1** Descripción..." o "- **1.a** Descripción..."
            elseif ($currentObjetivo && preg_match('/^- \*\*(\d+\.[0-9a-z]+)\*\* (.+)/', $line, $matches)) {
                OdsMeta::updateOrCreate(
                    ['clave' => $matches[1]],
                    [
                        'ods_objetivo_id' => $currentObjetivo->id,
                        'descripcion' => trim($matches[2]),
                    ]
                );
            }
        }

        $this->command->info("ODS cargados: " . OdsObjetivo::count() . " objetivos, " . OdsMeta::count() . " metas");
    }
}
