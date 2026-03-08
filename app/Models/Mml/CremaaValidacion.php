<?php

namespace App\Models\Mml;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CremaaValidacion extends Model
{
    protected $table = 'cremaa_validaciones';

    protected $fillable = [
        'indicador_id',
        'claro', 'claro_observacion',
        'relevante', 'relevante_observacion',
        'economico', 'economico_observacion',
        'monitoreable', 'monitoreable_observacion',
        'adecuado', 'adecuado_observacion',
        'aportante', 'aportante_observacion',
    ];

    protected function casts(): array
    {
        return [
            'claro' => 'boolean',
            'relevante' => 'boolean',
            'economico' => 'boolean',
            'monitoreable' => 'boolean',
            'adecuado' => 'boolean',
            'aportante' => 'boolean',
        ];
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class);
    }
}
