<?php

namespace App\Models\Mml;

use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoblacionPrograma extends Model
{
    protected $table = 'poblaciones_programa';

    protected $fillable = [
        'programa_id',
        'unidad_medida',
        'referencia_cantidad',
        'referencia_fuente',
        'potencial_cantidad',
        'potencial_fuente',
        'objetivo_cantidad',
        'objetivo_justificacion',
        'anio_ejercicio',
    ];

    protected $casts = [
        'referencia_cantidad' => 'integer',
        'potencial_cantidad' => 'integer',
        'objetivo_cantidad' => 'integer',
        'anio_ejercicio' => 'integer',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_id');
    }
}
