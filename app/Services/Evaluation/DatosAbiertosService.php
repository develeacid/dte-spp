<?php

namespace App\Services\Evaluation;

use App\Models\Evaluation\EvaluacionPrograma;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DatosAbiertosService
{
    private const BOM = "\xEF\xBB\xBF";

    private const COLUMNAS = [
        'programa_clave',
        'programa_nombre',
        'unidad_responsable',
        'indice_eficacia',
        'semaforos_verde',
        'semaforos_amarillo',
        'semaforos_rojo',
        'semaforos_sin_dato',
        'indicadores_evaluados',
        'indicadores_no_evaluados',
        'fecha_calculo',
    ];

    private const DICCIONARIO = [
        ['programa_clave', 'string', 'Clave del programa presupuestario', 'E001'],
        ['programa_nombre', 'string', 'Nombre del programa', 'Educación básica'],
        ['unidad_responsable', 'string', 'Nombre de la unidad responsable', 'Secretaría de Educación'],
        ['indice_eficacia', 'decimal', 'Índice de eficacia 0-200', '85.50'],
        ['semaforos_verde', 'integer', 'Indicadores en verde', '5'],
        ['semaforos_amarillo', 'integer', 'Indicadores en amarillo', '2'],
        ['semaforos_rojo', 'integer', 'Indicadores en rojo', '1'],
        ['semaforos_sin_dato', 'integer', 'Indicadores sin dato', '0'],
        ['indicadores_evaluados', 'integer', 'Total indicadores evaluados', '8'],
        ['indicadores_no_evaluados', 'integer', 'Indicadores sin evaluación', '2'],
        ['fecha_calculo', 'datetime', 'Fecha y hora del cálculo', '2026-01-15 10:30:00'],
    ];

    public function exportarCsv(int $ejercicio): string
    {
        $registros = $this->obtenerRegistros($ejercicio);

        $csv = self::BOM;
        $csv .= implode(',', self::COLUMNAS) . "\n";

        foreach ($registros as $registro) {
            $csv .= $this->registroACsvLinea($registro) . "\n";
        }

        return $csv;
    }

    public function exportarJson(int $ejercicio): array
    {
        $registros = $this->obtenerRegistros($ejercicio);

        $data = $registros->map(fn ($r) => $this->registroAArray($r))->values()->all();

        return [
            'metadata' => [
                'version' => '1.0',
                'fecha_generacion' => now()->toIso8601String(),
                'ejercicio_fiscal' => $ejercicio,
                'total_registros' => count($data),
            ],
            'data' => $data,
        ];
    }

    public function generarDiccionario(): string
    {
        $csv = self::BOM;
        $csv .= "campo,tipo,descripcion,ejemplo\n";

        foreach (self::DICCIONARIO as $fila) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', $v) . '"', $fila)) . "\n";
        }

        return $csv;
    }

    public function generarZip(int $ejercicio): string
    {
        $dir = 'datos-abiertos';
        Storage::disk('local')->makeDirectory($dir);

        $zipPath = storage_path("app/private/{$dir}/datos_abiertos_{$ejercicio}.zip");

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString("datos_{$ejercicio}.csv", $this->exportarCsv($ejercicio));
        $zip->addFromString("datos_{$ejercicio}.json", json_encode($this->exportarJson($ejercicio), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('diccionario_datos.csv', $this->generarDiccionario());

        $zip->close();

        return $zipPath;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, EvaluacionPrograma>
     */
    private function obtenerRegistros(int $ejercicio)
    {
        return EvaluacionPrograma::with('programa.team')
            ->where('ejercicio_fiscal', $ejercicio)
            ->orderBy('id')
            ->get();
    }

    private function registroAArray(EvaluacionPrograma $eval): array
    {
        $semaforos = $eval->conteo_semaforos ?? [];

        return [
            'programa_clave' => $eval->programa?->clave,
            'programa_nombre' => $eval->programa?->nombre,
            'unidad_responsable' => $eval->programa?->team?->name,
            'indice_eficacia' => (float) $eval->indice_eficacia,
            'semaforos_verde' => (int) ($semaforos['verde'] ?? 0),
            'semaforos_amarillo' => (int) ($semaforos['amarillo'] ?? 0),
            'semaforos_rojo' => (int) ($semaforos['rojo'] ?? 0),
            'semaforos_sin_dato' => (int) ($semaforos['sin_dato'] ?? 0),
            'indicadores_evaluados' => $eval->indicadores_evaluados,
            'indicadores_no_evaluados' => $eval->indicadores_no_evaluados,
            'fecha_calculo' => $eval->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function registroACsvLinea(EvaluacionPrograma $eval): string
    {
        $arr = $this->registroAArray($eval);

        return implode(',', array_map(function ($v) {
            if ($v === null) {
                return '';
            }
            if (is_string($v)) {
                return '"' . str_replace('"', '""', $v) . '"';
            }

            return $v;
        }, $arr));
    }
}
