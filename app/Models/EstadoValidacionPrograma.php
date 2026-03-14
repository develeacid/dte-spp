<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstadoValidacionPrograma extends Model
{
    protected $table = 'estado_validacion_programa';

    protected $fillable = [
        'programa_presupuestario_id',
        'ejercicio_fiscal',
        'planeacion_estado',
        'planeacion_detalle',
        'planeacion_actualizado_at',
        'juridico_estado',
        'juridico_detalle',
        'juridico_actualizado_at',
        'financiero_estado',
        'financiero_detalle',
        'financiero_actualizado_at',
        'consolidado',
        'validaciones_completas',
    ];

    protected function casts(): array
    {
        return [
            'ejercicio_fiscal' => 'integer',
            'planeacion_detalle' => 'array',
            'planeacion_actualizado_at' => 'datetime',
            'juridico_detalle' => 'array',
            'juridico_actualizado_at' => 'datetime',
            'financiero_detalle' => 'array',
            'financiero_actualizado_at' => 'datetime',
            'validaciones_completas' => 'integer',
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }
}
