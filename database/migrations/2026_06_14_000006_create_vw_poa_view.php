<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * vw_poa — Programa Operativo Anual como vista larga unificada (V2-D7).
     * Una fila por concepto, apilando el eje físico (metas por indicador) y el
     * financiero (gasto por partida) con discriminador `tipo`, pivoteados a T1-T4.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW vw_poa AS
            -- Eje FÍSICO: metas trimestrales por indicador
            SELECT
                mp.ejercicio_fiscal                                  AS ejercicio_fiscal,
                mn.team_id                                           AS team_id,
                pp.id                                                AS programa_id,
                pp.clave                                             AS programa_clave,
                pp.nombre                                            AS programa_nombre,
                'fisico'                                             AS tipo,
                i.id                                                 AS concepto_id,
                NULL::varchar                                        AS concepto_clave,
                i.nombre                                             AS concepto,
                mn.tipo_nivel                                        AS nivel,
                um.nombre                                            AS unidad,
                MAX(mp.meta_periodo) FILTER (WHERE mp.periodo = 1)   AS t1,
                MAX(mp.meta_periodo) FILTER (WHERE mp.periodo = 2)   AS t2,
                MAX(mp.meta_periodo) FILTER (WHERE mp.periodo = 3)   AS t3,
                MAX(mp.meta_periodo) FILTER (WHERE mp.periodo = 4)   AS t4,
                i.meta                                               AS total
            FROM metas_periodo mp
            JOIN indicadores i ON i.id = mp.indicador_id
            JOIN mir_niveles mn ON mn.id = i.mir_nivel_id
            JOIN programa_presupuestarios pp ON pp.id = mn.programa_presupuestario_id
            LEFT JOIN catalogo_unidades_medida um ON um.id = i.unidad_medida_id
            WHERE mp.activo = true AND pp.deleted_at IS NULL
            GROUP BY mp.ejercicio_fiscal, mn.team_id, pp.id, pp.clave, pp.nombre,
                     i.id, i.nombre, mn.tipo_nivel, um.nombre, i.meta

            UNION ALL

            -- Eje FINANCIERO: gasto trimestral por partida
            SELECT
                pa.ejercicio_fiscal                                       AS ejercicio_fiscal,
                pa.team_id                                                AS team_id,
                pp.id                                                     AS programa_id,
                pp.clave                                                  AS programa_clave,
                pp.nombre                                                 AS programa_nombre,
                'financiero'                                              AS tipo,
                pa.id                                                     AS concepto_id,
                pa.clave_partida                                          AS concepto_clave,
                pa.descripcion                                            AS concepto,
                NULL::varchar                                             AS nivel,
                'MXN'                                                     AS unidad,
                SUM(mgt.monto_programado) FILTER (WHERE mgt.trimestre = 1) AS t1,
                SUM(mgt.monto_programado) FILTER (WHERE mgt.trimestre = 2) AS t2,
                SUM(mgt.monto_programado) FILTER (WHERE mgt.trimestre = 3) AS t3,
                SUM(mgt.monto_programado) FILTER (WHERE mgt.trimestre = 4) AS t4,
                pa.monto_aprobado                                         AS total
            FROM partidas_presupuestales pa
            JOIN programa_presupuestarios pp ON pp.id = pa.programa_presupuestario_id
            LEFT JOIN metas_gasto_trimestral mgt ON mgt.partida_presupuestal_id = pa.id
            WHERE pp.deleted_at IS NULL
            GROUP BY pa.ejercicio_fiscal, pa.team_id, pp.id, pp.clave, pp.nombre,
                     pa.id, pa.clave_partida, pa.descripcion, pa.monto_aprobado
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_poa');
    }
};
