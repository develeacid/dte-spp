<?php

namespace App\Models\Mml;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicadorVariable extends Model
{
    protected $table = 'indicador_variables';

    protected $fillable = [
        'indicador_id', 'simbolo', 'nombre', 'descripcion',
        'comportamiento', 'unidad_medida_id', 'orden',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
        ];
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class);
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CatalogoUnidadMedida::class, 'unidad_medida_id');
    }
}
