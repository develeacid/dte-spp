<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * vw_presupuesto_aprobado (V2-E4) — presupuesto por programa × capítulo del ejercicio.
     * Capítulo derivado del primer dígito de la clave de partida (CONAC Objeto del Gasto).
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW vw_presupuesto_aprobado AS
            SELECT
                pa.ejercicio_fiscal                       AS ejercicio_fiscal,
                pa.team_id                                AS team_id,
                pp.id                                     AS programa_id,
                pp.clave                                  AS programa_clave,
                pp.nombre                                 AS programa_nombre,
                LEFT(pa.clave_partida, 1) || '000'        AS capitulo,
                SUM(pa.monto_aprobado)                    AS monto_aprobado,
                SUM(COALESCE(pa.monto_modificado, 0))     AS monto_modificado
            FROM partidas_presupuestales pa
            JOIN programa_presupuestarios pp ON pp.id = pa.programa_presupuestario_id
            WHERE pp.deleted_at IS NULL
            GROUP BY pa.ejercicio_fiscal, pa.team_id, pp.id, pp.clave, pp.nombre,
                     LEFT(pa.clave_partida, 1) || '000'
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_presupuesto_aprobado');
    }
};
