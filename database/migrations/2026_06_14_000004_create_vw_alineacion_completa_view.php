<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE OR REPLACE VIEW vw_alineacion_completa AS
            SELECT
                p.id               AS programa_id,
                p.clave            AS programa_clave,
                p.nombre           AS programa_nombre,
                p.ejercicio_fiscal AS ejercicio_fiscal,
                string_agg(DISTINCT eje.nombre, \'; \')        AS ped_eje,
                string_agg(DISTINCT peo.descripcion, \'; \')   AS ped_objetivo_estrategico,
                string_agg(DISTINCT pla.descripcion, \'; \')   AS ped_linea_accion,
                string_agg(DISTINCT pd.nombre, \'; \')         AS programas_derivados,
                string_agg(DISTINCT pndo.descripcion, \'; \')  AS pnd_objetivos,
                string_agg(DISTINCT odm.descripcion, \'; \')   AS ods_metas
            FROM programa_presupuestarios p
            LEFT JOIN mir_niveles mn ON mn.programa_presupuestario_id = p.id
            LEFT JOIN ped_objetivos_estrategicos peo ON peo.id = mn.ped_objetivo_estrategico_id
            LEFT JOIN ped_temas tema ON tema.id = peo.ped_tema_id
            LEFT JOIN ped_ejes eje ON eje.id = tema.ped_eje_id
            LEFT JOIN ped_lineas_accion pla ON pla.id = mn.ped_linea_accion_id
            LEFT JOIN programas_derivados_objetivos pdo ON pdo.id = mn.programa_derivado_objetivo_id
            LEFT JOIN programas_derivados pd ON pd.id = pdo.programa_derivado_id
            LEFT JOIN alineacion_ped_pnd app ON app.ped_objetivo_estrategico_id = peo.id
            LEFT JOIN pnd_objetivos pndo ON pndo.id = app.pnd_objetivo_id
            LEFT JOIN alineacion_pnd_ods apo ON apo.pnd_objetivo_id = pndo.id
            LEFT JOIN ods_metas odm ON odm.id = apo.ods_meta_id
            WHERE p.deleted_at IS NULL
            GROUP BY p.id, p.clave, p.nombre, p.ejercicio_fiscal
        ');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_alineacion_completa');
    }
};
