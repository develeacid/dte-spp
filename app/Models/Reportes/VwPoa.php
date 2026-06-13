<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

/**
 * Vista read-only del Programa Operativo Anual (V2-D7).
 * Vista larga unificada: una fila por concepto, con `tipo` = fisico|financiero,
 * metas/montos pivoteados a t1..t4 + total. Respaldada por la vista SQL `vw_poa`.
 */
class VwPoa extends Model
{
    protected $table = 'vw_poa';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'ejercicio_fiscal' => 'integer',
        't1' => 'decimal:2',
        't2' => 'decimal:2',
        't3' => 'decimal:2',
        't4' => 'decimal:2',
        'total' => 'decimal:2',
    ];
}
