<?php

namespace App\Models\Reportes;

use App\Services\Presupuesto\CogCapituloCategorizer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Vista read-only del presupuesto aprobado/modificado por programa × capítulo (V2-E4).
 * Respaldada por la vista SQL `vw_presupuesto_aprobado`.
 */
class VwPresupuestoAprobado extends Model
{
    protected $table = 'vw_presupuesto_aprobado';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'ejercicio_fiscal' => 'integer',
        'monto_aprobado' => 'decimal:2',
        'monto_modificado' => 'decimal:2',
    ];

    /** Etiqueta CONAC del capítulo, reusando el categorizador existente. */
    protected function capituloLabel(): Attribute
    {
        return Attribute::make(get: fn () => CogCapituloCategorizer::label((string) $this->capitulo));
    }
}
