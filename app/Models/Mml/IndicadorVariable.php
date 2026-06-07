<?php

namespace App\Models\Mml;

use App\Models\CatalogoUnidadMedida;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicadorVariable extends Model
{
    protected $table = 'indicador_variables';

    protected $fillable = [
        'indicador_id', 'simbolo', 'nombre', 'descripcion', 'fuente',
        'comportamiento', 'unidad_medida_id', 'orden',
        'geobase_endpoint_type', 'spp_reference_id', 'geobase_filter_params', 'geobase_value_key',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'geobase_filter_params' => 'array',
            'spp_reference_id' => 'integer',
        ];
    }

    /**
     * The variable resolves a numeric value from GeoBase when both the
     * endpoint type and the spp_reference_id are set. Reference is always
     * a dte-spp id (programa.id for program_coverage,
     * mir_nivel.id for component_coverage).
     */
    public function hasGeoBaseLink(): bool
    {
        return $this->geobase_endpoint_type !== null && $this->spp_reference_id !== null;
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class);
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(CatalogoUnidadMedida::class, 'unidad_medida_id');
    }
}
