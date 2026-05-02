<?php

namespace App\Services\Mml;

use App\DTOs\ImportedMirData;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class MirParserService
{
    /**
     * Parse a MIR from Markdown content.
     *
     * Expected format:
     *   # Programa: Nombre del Programa (CLAVE)
     *   ## Ejercicio: 2026
     *   | Nivel | Resumen Narrativo | Indicador | Formula | ... |
     */
    public function fromMarkdown(string $content): ImportedMirData
    {
        $lines = explode("\n", $content);

        $nombre = null;
        $clave = null;
        $ejercicioFiscal = null;
        $rows = [];
        $inTable = false;
        $headerSkipped = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Parse header: # Programa: Name (Code)
            if (preg_match('/^#\s+Programa:\s*(.+?)\s*\((\S+)\)\s*$/', $trimmed, $m)) {
                $nombre = trim($m[1]);
                $clave = trim($m[2]);

                continue;
            }

            // Parse ejercicio: ## Ejercicio: 2026
            if (preg_match('/^##\s+Ejercicio:\s*(\d{4})/', $trimmed, $m)) {
                $ejercicioFiscal = (int) $m[1];

                continue;
            }

            // Detect table rows (pipe-delimited)
            if (str_starts_with($trimmed, '|')) {
                if (! $inTable) {
                    $inTable = true;

                    // First row is the header — skip it
                    continue;
                }

                // Skip separator row (|---|---|...)
                if (preg_match('/^\|[\s\-:|]+\|$/', $trimmed)) {
                    continue;
                }

                $cells = array_map('trim', explode('|', $trimmed));
                // Remove empty first/last from leading/trailing pipes
                array_shift($cells);
                array_pop($cells);

                if (count($cells) >= 2) {
                    $rows[] = $cells;
                }
            }
        }

        $niveles = $this->buildNiveles($rows);

        return new ImportedMirData(
            nombre: $nombre,
            clave: $clave,
            ejercicioFiscal: $ejercicioFiscal,
            niveles: $niveles,
        );
    }

    /**
     * Parse a MIR from a CSV file path.
     * Columns: Nivel, Resumen Narrativo, Indicador, Formula, Tipo, Dimension,
     *          Frecuencia, Medio Verificacion, Supuestos
     */
    public function fromCsv(string $path): ImportedMirData
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("No se pudo abrir el archivo CSV: {$path}");
        }

        $rows = [];
        $isHeader = true;

        while (($row = fgetcsv($handle)) !== false) {
            if ($isHeader) {
                $isHeader = false;

                continue;
            }
            $rows[] = $row;
        }

        fclose($handle);

        $niveles = $this->buildNiveles($rows);

        return new ImportedMirData(
            nombre: null,
            clave: null,
            ejercicioFiscal: null,
            niveles: $niveles,
        );
    }

    /**
     * Parse a MIR from an XLSX file path.
     * Same column structure as CSV.
     */
    public function fromExcel(string $path): ImportedMirData
    {
        if (! class_exists(Excel::class)) {
            throw new \RuntimeException('maatwebsite/laravel-excel is not installed.');
        }

        $data = Excel::toArray(new class implements WithHeadingRow {}, $path);

        $rows = [];
        if (! empty($data[0])) {
            foreach ($data[0] as $row) {
                $rows[] = array_values($row);
            }
        }

        $niveles = $this->buildNiveles($rows);

        return new ImportedMirData(
            nombre: null,
            clave: null,
            ejercicioFiscal: null,
            niveles: $niveles,
        );
    }

    /**
     * Build niveles array from tabular rows.
     *
     * Columns expected (by index):
     *   0: Nivel, 1: Resumen Narrativo, 2: Indicador, 3: Formula,
     *   4: Tipo, 5: Dimension, 6: Frecuencia, 7: Medio Verificacion, 8: Supuestos
     */
    private function buildNiveles(array $rows): array
    {
        $niveles = [];
        $currentIndex = -1;
        $componenteCount = 0;
        $orden = 0;

        foreach ($rows as $row) {
            $tipoRaw = trim($row[0] ?? '');
            $resumen = trim($row[1] ?? '');
            $indicadorNombre = trim($row[2] ?? '');
            $formula = trim($row[3] ?? '');
            $tipo = trim($row[4] ?? '');
            $dimension = trim($row[5] ?? '');
            $frecuencia = trim($row[6] ?? '');
            $medio = trim($row[7] ?? '');
            $supuestos = trim($row[8] ?? '');

            $tipoNivel = $this->normalizeTipoNivel($tipoRaw);

            // New nivel row (has a tipo_nivel value)
            if ($tipoNivel !== null) {
                $orden++;
                $componenteIdx = null;

                if ($tipoNivel === 'componente') {
                    $componenteCount++;
                }

                if ($tipoNivel === 'actividad') {
                    $componenteIdx = $componenteCount > 0 ? $componenteCount - 1 : null;
                }

                $nivel = [
                    'tipo_nivel' => $tipoNivel,
                    'resumen_narrativo' => $resumen ?: null,
                    'supuestos' => $supuestos ?: null,
                    'orden' => $orden,
                    'componente_idx' => $componenteIdx,
                    'indicadores' => [],
                ];

                // Add indicator if present on the same row
                if ($indicadorNombre !== '') {
                    $nivel['indicadores'][] = $this->buildIndicador($indicadorNombre, $formula, $tipo, $dimension, $frecuencia, $medio);
                }

                $niveles[] = $nivel;
                $currentIndex = count($niveles) - 1;
            } else {
                // Continuation row — add indicator to current nivel
                if ($currentIndex >= 0 && $indicadorNombre !== '') {
                    $niveles[$currentIndex]['indicadores'][] = $this->buildIndicador($indicadorNombre, $formula, $tipo, $dimension, $frecuencia, $medio);
                }
            }
        }

        return $niveles;
    }

    private function buildIndicador(
        string $nombre,
        string $formula,
        string $tipo,
        string $dimension,
        string $frecuencia,
        string $medio,
    ): array {
        $indicador = [
            'nombre' => $nombre,
            'formula_texto' => $formula ?: null,
            'tipo' => $this->normalizeEnum($tipo) ?: null,
            'dimension' => $this->normalizeEnum($dimension) ?: null,
            'frecuencia' => $this->normalizeEnum($frecuencia) ?: null,
            'sentido' => null,
            'linea_base' => null,
            'meta' => null,
            'rangos_semaforo' => null,
            'variables' => [],
            'medios' => [],
        ];

        if ($medio !== '') {
            $indicador['medios'][] = ['nombre' => $medio, 'fuente' => null];
        }

        return $indicador;
    }

    /**
     * Normalize tipo nivel string (handles accents, case).
     */
    private function normalizeTipoNivel(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($raw));

        // Remove accents
        $normalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $normalized,
        );

        $map = [
            'fin' => 'fin',
            'proposito' => 'proposito',
            'componente' => 'componente',
            'actividad' => 'actividad',
        ];

        return $map[$normalized] ?? null;
    }

    /**
     * Normalize an enum value (lowercase, remove accents).
     */
    private function normalizeEnum(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($value));

        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $normalized,
        );
    }
}
