<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige el desfase entre los títulos del catálogo y el contenido real
     * de las tablas pub_* para DS-01..DS-04. Antes del fix, cada clave decía
     * el nombre del dataset "siguiente" (DS-01 anunciaba MIR pero pub_programas
     * contiene programas presupuestales, etc.).
     *
     * Idempotente: el WHERE por nombre antiguo evita pisar ediciones del RDA.
     */
    public function up(): void
    {
        $cambios = [
            'DS-01' => [
                'nombre_viejo' => 'MIR por Programa y Ejercicio Fiscal',
                'descripcion_vieja' => 'Matriz de Indicadores para Resultados (MIR) completa de cada programa presupuestal del ejercicio fiscal vigente, incluyendo niveles Fin, Propósito, Componente y Actividad.',
                'nombre_nuevo' => 'Programas Presupuestales',
                'descripcion_nueva' => 'Catálogo de programas presupuestales por ejercicio fiscal, con clave, nombre, unidad responsable y modalidad.',
            ],
            'DS-02' => [
                'nombre_viejo' => 'Avances Trimestrales de Indicadores',
                'descripcion_vieja' => 'Resultados trimestrales reportados por unidades responsables sobre cada indicador del MIR, con valor alcanzado, meta del trimestre y semáforo.',
                'nombre_nuevo' => 'Matriz de Indicadores para Resultados (MIR)',
                'descripcion_nueva' => 'Matriz de Indicadores para Resultados (MIR) completa de cada programa presupuestal del ejercicio fiscal vigente, incluyendo niveles Fin, Propósito, Componente y Actividad.',
            ],
            'DS-03' => [
                'nombre_viejo' => 'Evaluación Anual de Programas',
                'descripcion_vieja' => 'Resultado consolidado de la evaluación anual de cada programa: índice de eficacia, semáforo final, cumplimiento normativo y observaciones.',
                'nombre_nuevo' => 'Avances Trimestrales de Indicadores',
                'descripcion_nueva' => 'Resultados trimestrales reportados por unidades responsables sobre cada indicador del MIR, con valor alcanzado, meta del trimestre y semáforo.',
            ],
            'DS-04' => [
                'nombre_viejo' => 'Catálogo de Indicadores con Ficha Técnica',
                'descripcion_vieja' => 'Resumen de fichas técnicas de indicadores: nombre, dimensión, tipo, frecuencia, fórmula y método de cálculo. Excluye fuentes de datos sensibles.',
                'nombre_nuevo' => 'Evaluación Anual de Programas',
                'descripcion_nueva' => 'Resultado consolidado de la evaluación anual de cada programa: índice de eficacia, semáforo final, cumplimiento normativo y observaciones.',
            ],
        ];

        foreach ($cambios as $clave => $c) {
            DB::table('datasets_abiertos')
                ->where('dataset_clave', $clave)
                ->whereNull('periodo')
                ->where('nombre', $c['nombre_viejo'])
                ->update([
                    'nombre' => $c['nombre_nuevo'],
                    'updated_at' => now(),
                ]);

            DB::table('datasets_abiertos')
                ->where('dataset_clave', $clave)
                ->whereNull('periodo')
                ->where('descripcion', $c['descripcion_vieja'])
                ->update([
                    'descripcion' => $c['descripcion_nueva'],
                    'updated_at' => now(),
                ]);
        }

        $this->resyncPublicCatalog();
    }

    public function down(): void
    {
        $reverso = [
            'DS-01' => [
                'nombre_nuevo' => 'MIR por Programa y Ejercicio Fiscal',
                'descripcion_nueva' => 'Matriz de Indicadores para Resultados (MIR) completa de cada programa presupuestal del ejercicio fiscal vigente, incluyendo niveles Fin, Propósito, Componente y Actividad.',
                'nombre_viejo' => 'Programas Presupuestales',
                'descripcion_vieja' => 'Catálogo de programas presupuestales por ejercicio fiscal, con clave, nombre, unidad responsable y modalidad.',
            ],
            'DS-02' => [
                'nombre_nuevo' => 'Avances Trimestrales de Indicadores',
                'descripcion_nueva' => 'Resultados trimestrales reportados por unidades responsables sobre cada indicador del MIR, con valor alcanzado, meta del trimestre y semáforo.',
                'nombre_viejo' => 'Matriz de Indicadores para Resultados (MIR)',
                'descripcion_vieja' => 'Matriz de Indicadores para Resultados (MIR) completa de cada programa presupuestal del ejercicio fiscal vigente, incluyendo niveles Fin, Propósito, Componente y Actividad.',
            ],
            'DS-03' => [
                'nombre_nuevo' => 'Evaluación Anual de Programas',
                'descripcion_nueva' => 'Resultado consolidado de la evaluación anual de cada programa: índice de eficacia, semáforo final, cumplimiento normativo y observaciones.',
                'nombre_viejo' => 'Avances Trimestrales de Indicadores',
                'descripcion_vieja' => 'Resultados trimestrales reportados por unidades responsables sobre cada indicador del MIR, con valor alcanzado, meta del trimestre y semáforo.',
            ],
            'DS-04' => [
                'nombre_nuevo' => 'Catálogo de Indicadores con Ficha Técnica',
                'descripcion_nueva' => 'Resumen de fichas técnicas de indicadores: nombre, dimensión, tipo, frecuencia, fórmula y método de cálculo. Excluye fuentes de datos sensibles.',
                'nombre_viejo' => 'Evaluación Anual de Programas',
                'descripcion_vieja' => 'Resultado consolidado de la evaluación anual de cada programa: índice de eficacia, semáforo final, cumplimiento normativo y observaciones.',
            ],
        ];

        foreach ($reverso as $clave => $c) {
            DB::table('datasets_abiertos')
                ->where('dataset_clave', $clave)
                ->whereNull('periodo')
                ->where('nombre', $c['nombre_viejo'])
                ->update([
                    'nombre' => $c['nombre_nuevo'],
                    'updated_at' => now(),
                ]);

            DB::table('datasets_abiertos')
                ->where('dataset_clave', $clave)
                ->whereNull('periodo')
                ->where('descripcion', $c['descripcion_vieja'])
                ->update([
                    'descripcion' => $c['descripcion_nueva'],
                    'updated_at' => now(),
                ]);
        }

        $this->resyncPublicCatalog();
    }

    /**
     * Replica la lógica de DatasetsCatalogoPublisher: DELETE + INSERT desde
     * datasets_abiertos publicados. Inlineamos en SQL para no acoplar la
     * migración al servicio (que podría cambiar).
     */
    private function resyncPublicCatalog(): void
    {
        if (! Schema::connection('pgsql_public')->hasTable('pub_datasets_catalogo')) {
            return;
        }

        DB::connection('pgsql_public')->transaction(function () {
            DB::connection('pgsql_public')->table('pub_datasets_catalogo')->delete();

            $publicados = DB::table('datasets_abiertos')
                ->where('status', 'publicado')
                ->whereNull('periodo')
                ->orderBy('dataset_clave')
                ->get(['dataset_clave', 'nombre', 'descripcion', 'publicado_en']);

            if ($publicados->isEmpty()) {
                return;
            }

            $now = now();
            $rows = $publicados->map(fn ($d) => [
                'codigo' => $d->dataset_clave,
                'titulo' => $d->nombre,
                'descripcion' => $d->descripcion ?? '',
                'fecha_publicacion' => $d->publicado_en
                    ? \Illuminate\Support\Carbon::parse($d->publicado_en)->toDateString()
                    : null,
                'frecuencia_actualizacion' => null,
                'url_csv' => null,
                'url_json' => null,
                'total_registros' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DB::connection('pgsql_public')->table('pub_datasets_catalogo')->insert($rows);
        });
    }
};
