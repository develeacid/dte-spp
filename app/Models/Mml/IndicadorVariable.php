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
        'geobase_endpoint_type', 'geobase_reference_id', 'geobase_filter_params', 'geobase_value_key',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'geobase_filter_params' => 'array',
            'geobase_reference_id' => 'integer',
        ];
    }

    public function hasGeoBaseLink(): bool
    {
        return $this->geobase_endpoint_type !== null && $this->geobase_reference_id !== null;
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
