<?php

namespace App\Models\Mml;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CremaValidacionMv extends Model
{
    protected $table = 'crema_validaciones_mv';

    protected $fillable = [
        'medio_verificacion_id',
        'confiable',
        'confiable_observacion',
        'relevante',
        'relevante_observacion',
        'economico',
        'economico_observacion',
        'monitoreable',
        'monitoreable_observacion',
        'asequible',
        'asequible_observacion',
    ];

    protected $casts = [
        'confiable' => 'boolean',
        'relevante' => 'boolean',
        'economico' => 'boolean',
        'monitoreable' => 'boolean',
        'asequible' => 'boolean',
    ];

    public function medioVerificacion(): BelongsTo
    {
        return $this->belongsTo(MedioVerificacion::class, 'medio_verificacion_id');
    }
}
