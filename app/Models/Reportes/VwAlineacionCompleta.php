<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

/**
 * Vista read-only de alineación estratégica completa (V2-E9).
 * Una fila por Programa Presupuestario con su cadena ascendente resuelta:
 * PED (Eje/Objetivo/Línea) + Programas Derivados + PND + ODS.
 * Respaldada por la vista SQL `vw_alineacion_completa` (no escribible).
 */
class VwAlineacionCompleta extends Model
{
    protected $table = 'vw_alineacion_completa';

    protected $primaryKey = 'programa_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
