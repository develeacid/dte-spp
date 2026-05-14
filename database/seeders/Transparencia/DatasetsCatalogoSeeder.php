<?php

namespace Database\Seeders\Transparencia;

use App\Models\Transparencia\DatasetAbierto;
use Illuminate\Database\Seeder;

/**
 * Carga el catálogo de plantillas de datasets abiertos (DS-01..DS-G04).
 *
 * Diseñado como snapshot del estado inicial: usa `firstOrCreate` para que
 * un re-seed en un entorno con datos no pise ediciones del RDA en
 * `dcat_metadata`, `nombre` o `descripcion`. Para propagar cambios al texto
 * fuente del catálogo en producción, usar una migración explícita de datos
 * (no este seeder).
 */
class DatasetsCatalogoSeeder extends Seeder
{
    private const DCAT_BASE = [
        'dct:publisher' => 'Secretaría de Desarrollo Económico del Estado',
        'dct:license' => 'https://creativecommons.org/licenses/by/4.0/',
        'dct:language' => 'es',
        'dcat:contactPoint' => ['vcard:fn' => 'Unidad de Transparencia'],
    ];

    public function run(): void
    {
        foreach ($this->catalogo() as $entry) {
            DatasetAbierto::firstOrCreate(
                ['dataset_clave' => $entry['dataset_clave'], 'periodo' => null],
                [
                    'nombre' => $entry['nombre'],
                    'descripcion' => $entry['descripcion'],
                    'sistema_origen' => $entry['sistema_origen'],
                    'status' => 'borrador',
                    'creado_por' => null,
                    'dcat_metadata' => self::DCAT_BASE,
                ]
            );
        }
    }

    private function catalogo(): array
    {
        return [
            ['dataset_clave' => 'DS-01', 'sistema_origen' => 'spp',
                'nombre' => 'Programas Presupuestales',
                'descripcion' => 'Catálogo de programas presupuestales por ejercicio fiscal, con clave, nombre, unidad responsable y modalidad.'],
            ['dataset_clave' => 'DS-02', 'sistema_origen' => 'spp',
                'nombre' => 'Matriz de Indicadores para Resultados (MIR)',
                'descripcion' => 'Matriz de Indicadores para Resultados (MIR) completa de cada programa presupuestal del ejercicio fiscal vigente, incluyendo niveles Fin, Propósito, Componente y Actividad.'],
            ['dataset_clave' => 'DS-03', 'sistema_origen' => 'spp',
                'nombre' => 'Avances Trimestrales de Indicadores',
                'descripcion' => 'Resultados trimestrales reportados por unidades responsables sobre cada indicador del MIR, con valor alcanzado, meta del trimestre y semáforo.'],
            ['dataset_clave' => 'DS-04', 'sistema_origen' => 'spp',
                'nombre' => 'Evaluación Anual de Programas',
                'descripcion' => 'Resultado consolidado de la evaluación anual de cada programa: índice de eficacia, semáforo final, cumplimiento normativo y observaciones.'],
            ['dataset_clave' => 'DS-05', 'sistema_origen' => 'spp',
                'nombre' => 'Alineación Estratégica',
                'descripcion' => 'Árbol de alineación de cada programa con el PND, PED, ODS y Programa Derivado correspondiente.'],
            ['dataset_clave' => 'DS-06', 'sistema_origen' => 'spp',
                'nombre' => 'Resumen de Cobertura por Ejercicio',
                'descripcion' => 'Totales agregados de beneficiarios atendidos por programa y ejercicio (datos provenientes de GeoBase, sin desagregación geográfica granular).'],
            ['dataset_clave' => 'DS-G01', 'sistema_origen' => 'geobase',
                'nombre' => 'Cobertura Agregada por Municipio y Programa',
                'descripcion' => 'Conteo de beneficiarios por municipio y programa, con supresión de celdas con menos de 5 beneficiarios (k≥5).'],
            ['dataset_clave' => 'DS-G02', 'sistema_origen' => 'geobase',
                'nombre' => 'Desagregación Demográfica por Programa (Anexo 11)',
                'descripcion' => 'Desagregación por sexo, grupos de edad y discapacidad para reportes Anexo 11 del PEF, con anonimización k≥5.'],
            ['dataset_clave' => 'DS-G03', 'sistema_origen' => 'geobase',
                'nombre' => 'Cobertura Geográfica',
                'descripcion' => 'Polígonos GeoJSON de cobertura municipal por programa. No incluye ubicaciones puntuales de beneficiarios.'],
            ['dataset_clave' => 'DS-G04', 'sistema_origen' => 'geobase',
                'nombre' => 'Evolución Temporal de Beneficiarios',
                'descripcion' => 'Series trimestrales del total de beneficiarios atendidos por programa, agregadas a nivel estatal.'],
        ];
    }
}
