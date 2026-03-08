<?php

namespace App\Services;

use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PedMarkdownParser
{
    protected array $errors = [];
    protected array $tree = [];

    /**
     * Parsea un archivo Markdown y retorna la estructura en árbol.
     */
    public function parse(string $content): array
    {
        $this->errors = [];
        $this->tree = [];

        $lines = explode("\n", $content);
        $lineNumber = 0;

        // Índices actuales para tracking en $this->tree
        $hasPlan = false;
        $ejeIdx = -1;
        $temaIdx = -1;
        $objIdx = -1;
        $estIdx = -1;

        foreach ($lines as $line) {
            $lineNumber++;
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $level = $this->detectHeadingLevel($line);

            if ($level === null) {
                if (Str::startsWith($line, '-')) {
                    if ($estIdx < 0) {
                        $this->addError($lineNumber, "Línea de acción sin estrategia padre");
                        continue;
                    }

                    $lineaAccion = $this->parseLineaAccion($line);
                    if ($lineaAccion) {
                        $this->tree['ejes'][$ejeIdx]['temas'][$temaIdx]['objetivos'][$objIdx]['estrategias'][$estIdx]['lineas'][] = $lineaAccion;
                    }
                }
                continue;
            }

            if (!$this->validateHierarchy($level, $lineNumber)) {
                continue;
            }

            switch ($level) {
                case 1: // Plan
                    $this->tree = $this->parsePlan($line);
                    $hasPlan = true;
                    $ejeIdx = $temaIdx = $objIdx = $estIdx = -1;
                    break;

                case 2: // Eje
                    if (!$hasPlan) {
                        $this->addError($lineNumber, "Eje sin plan padre");
                        continue 2;
                    }
                    $this->tree['ejes'][] = $this->parseEje($line);
                    $ejeIdx = count($this->tree['ejes']) - 1;
                    $temaIdx = $objIdx = $estIdx = -1;
                    break;

                case 3: // Tema
                    if ($ejeIdx < 0) {
                        $this->addError($lineNumber, "Tema sin eje padre");
                        continue 2;
                    }
                    $this->tree['ejes'][$ejeIdx]['temas'][] = $this->parseTema($line);
                    $temaIdx = count($this->tree['ejes'][$ejeIdx]['temas']) - 1;
                    $objIdx = $estIdx = -1;
                    break;

                case 4: // Objetivo Estratégico
                    if ($temaIdx < 0) {
                        $this->addError($lineNumber, "Objetivo estratégico sin tema padre");
                        continue 2;
                    }
                    $this->tree['ejes'][$ejeIdx]['temas'][$temaIdx]['objetivos'][] = $this->parseObjetivo($line);
                    $objIdx = count($this->tree['ejes'][$ejeIdx]['temas'][$temaIdx]['objetivos']) - 1;
                    $estIdx = -1;
                    break;

                case 5: // Estrategia
                    if ($objIdx < 0) {
                        $this->addError($lineNumber, "Estrategia sin objetivo padre");
                        continue 2;
                    }
                    $this->tree['ejes'][$ejeIdx]['temas'][$temaIdx]['objetivos'][$objIdx]['estrategias'][] = $this->parseEstrategia($line);
                    $estIdx = count($this->tree['ejes'][$ejeIdx]['temas'][$temaIdx]['objetivos'][$objIdx]['estrategias']) - 1;
                    break;
            }
        }

        // Validar que se encontró al menos un plan
        if (empty($this->tree)) {
            $this->addError(0, "No se encontró ningún plan en el archivo");
        }

        return [
            'tree' => $this->tree,
            'errors' => $this->errors,
            'valid' => empty($this->errors),
        ];
    }

    /**
     * Detecta el nivel de heading (1-5).
     */
    protected function detectHeadingLevel(string $line): ?int
    {
        if (Str::startsWith($line, '#####')) return 5;
        if (Str::startsWith($line, '####')) return 4;
        if (Str::startsWith($line, '###')) return 3;
        if (Str::startsWith($line, '##')) return 2;
        if (Str::startsWith($line, '#')) return 1;

        return null;
    }

    /**
     * Valida que la jerarquía sea correcta.
     */
    protected function validateHierarchy(int $level, int $lineNumber): bool
    {
        // El primer elemento debe ser un plan (nivel 1)
        if (empty($this->tree) && $level !== 1) {
            $this->addError($lineNumber, "El archivo debe comenzar con un Plan (heading nivel 1)");
            return false;
        }

        return true;
    }

    /**
     * Parsea un Plan (H1).
     */
    protected function parsePlan(string $line): array
    {
        $text = ltrim($line, '# ');

        // Intentar extraer periodo
        $periodoMatch = preg_match('/(\d{4})\s*[-\x{2013}\x{2014}]\s*(\d{4})/u', $text, $matches);

        return [
            'tipo' => 'plan',
            'nombre' => $text,
            'periodo_inicio' => $periodoMatch ? (int) $matches[1] : null,
            'periodo_fin' => $periodoMatch ? (int) $matches[2] : null,
            'ejes' => [],
        ];
    }

    /**
     * Parsea un Eje (H2).
     */
    protected function parseEje(string $line): array
    {
        $text = ltrim($line, '# ');

        // Formato esperado: "Eje 1: Nombre" o "Eje 1. Nombre"
        if (preg_match('/^Eje\s+(\d+)\s*[:.-]\s*(.+)$/i', $text, $matches)) {
            return [
                'tipo' => 'eje',
                'numero' => $matches[1],
                'nombre' => trim($matches[2]),
                'temas' => [],
            ];
        }

        // Si no coincide el formato, usar el texto completo
        return [
            'tipo' => 'eje',
            'numero' => '1',
            'nombre' => $text,
            'temas' => [],
        ];
    }

    /**
     * Parsea un Tema (H3).
     */
    protected function parseTema(string $line): array
    {
        $text = ltrim($line, '# ');

        // Formato esperado: "Tema 1.1: Nombre" o "Tema 1.1 Nombre"
        if (preg_match('/^Tema\s+([\d.]+)\s*[:.-]?\s*(.*)$/i', $text, $matches)) {
            return [
                'tipo' => 'tema',
                'numero' => $matches[1],
                'nombre' => trim($matches[2]) ?: $text,
            ];
        }

        return [
            'tipo' => 'tema',
            'numero' => '1',
            'nombre' => $text,
        ];
    }

    /**
     * Parsea un Objetivo Estratégico (H4).
     */
    protected function parseObjetivo(string $line): array
    {
        $text = ltrim($line, '# ');

        // Formato esperado: "Objetivo 1.1.1: Descripción" o solo la descripción
        if (preg_match('/^Objetivo\s+([\d.]+)\s*[:.-]?\s*(.*)$/i', $text, $matches)) {
            return [
                'tipo' => 'objetivo',
                'clave' => $matches[1],
                'descripcion' => trim($matches[2]) ?: $text,
                'estrategias' => [],
            ];
        }

        return [
            'tipo' => 'objetivo',
            'clave' => '1',
            'descripcion' => $text,
            'estrategias' => [],
        ];
    }

    /**
     * Parsea una Estrategia (H5).
     */
    protected function parseEstrategia(string $line): array
    {
        $text = ltrim($line, '# ');

        // Formato esperado: "Estrategia 1.1.1.1: Descripción"
        if (preg_match('/^Estrategia\s+([\d.]+)\s*[:.-]?\s*(.*)$/i', $text, $matches)) {
            return [
                'tipo' => 'estrategia',
                'clave' => $matches[1],
                'descripcion' => trim($matches[2]) ?: $text,
                'lineas' => [],
            ];
        }

        return [
            'tipo' => 'estrategia',
            'clave' => '1',
            'descripcion' => $text,
            'lineas' => [],
        ];
    }

    /**
     * Parsea una Línea de Acción (lista).
     */
    protected function parseLineaAccion(string $line): ?array
    {
        $text = ltrim($line, '- ');

        // Formato esperado: "Línea de Acción 1.1.1.1.1: Descripción"
        if (preg_match('/^L[ií]nea\s+de\s+Acci[oó]n\s+([\d.]+)\s*[:.-]?\s*(.*)$/i', $text, $matches)) {
            return [
                'tipo' => 'linea',
                'clave' => $matches[1],
                'descripcion' => trim($matches[2]) ?: $text,
            ];
        }

        // Si no tiene formato específico, asumir que es solo descripción
        return [
            'tipo' => 'linea',
            'clave' => '1',
            'descripcion' => $text,
        ];
    }

    /**
     * Agrega un error a la lista.
     */
    protected function addError(int $line, string $message): void
    {
        $this->errors[] = [
            'line' => $line,
            'message' => $message,
        ];
    }

    /**
     * Importa el árbol parseado a la base de datos.
     */
    public function import(array $tree, bool $activatePlan = true): PedPlan
    {
        return DB::transaction(function () use ($tree, $activatePlan) {
            // Desactivar otros planes si este será activo
            if ($activatePlan) {
                PedPlan::query()->update(['activo' => false]);
            }

            // Crear Plan
            $plan = PedPlan::create([
                'nombre' => $tree['nombre'],
                'nivel_gobierno' => 'estatal',
                'periodo_inicio' => $tree['periodo_inicio'] ?? now()->year,
                'periodo_fin' => $tree['periodo_fin'] ?? now()->year + 5,
                'activo' => $activatePlan,
            ]);

            // Crear Ejes
            foreach ($tree['ejes'] ?? [] as $ejeData) {
                $eje = $plan->ejes()->create([
                    'numero' => $ejeData['numero'],
                    'nombre' => $ejeData['nombre'],
                ]);

                // Crear Temas
                foreach ($ejeData['temas'] ?? [] as $temaData) {
                    $tema = $eje->temas()->create([
                        'numero' => $temaData['numero'],
                        'nombre' => $temaData['nombre'],
                    ]);

                    // Crear Objetivos
                    foreach ($temaData['objetivos'] ?? [] as $objData) {
                        $objetivo = $tema->objetivosEstrategicos()->create([
                            'clave' => $objData['clave'],
                            'descripcion' => $objData['descripcion'],
                        ]);

                        // Crear Estrategias
                        foreach ($objData['estrategias'] ?? [] as $estData) {
                            $estrategia = $objetivo->estrategias()->create([
                                'clave' => $estData['clave'],
                                'descripcion' => $estData['descripcion'],
                            ]);

                            // Crear Líneas de Acción
                            foreach ($estData['lineas'] ?? [] as $lineaData) {
                                $estrategia->lineasAccion()->create([
                                    'clave' => $lineaData['clave'],
                                    'descripcion' => $lineaData['descripcion'],
                                ]);
                            }
                        }
                    }
                }
            }

            return $plan;
        });
    }

    /**
     * Parsea archivo y retorna estadísticas.
     */
    public function getStats(array $parsed): array
    {
        $tree = $parsed['tree'];

        return [
            'plan' => !empty($tree) ? 1 : 0,
            'ejes' => count($tree['ejes'] ?? []),
            'temas' => $this->countRecursive($tree['ejes'] ?? [], 'temas'),
            'objetivos' => $this->countRecursive($tree['ejes'] ?? [], 'temas', 'objetivos'),
            'estrategias' => $this->countRecursive($tree['ejes'] ?? [], 'temas', 'objetivos', 'estrategias'),
            'lineas' => $this->countRecursive($tree['ejes'] ?? [], 'temas', 'objetivos', 'estrategias', 'lineas'),
        ];
    }

    /**
     * Cuenta elementos recursivamente en el árbol.
     */
    protected function countRecursive(array $data, string ...$keys): int
    {
        if (empty($keys)) {
            return count($data);
        }

        $key = array_shift($keys);
        $total = 0;

        foreach ($data as $item) {
            if (isset($item[$key])) {
                if (empty($keys)) {
                    $total += count($item[$key]);
                } else {
                    $total += $this->countRecursive($item[$key], ...$keys);
                }
            }
        }

        return $total;
    }
}
