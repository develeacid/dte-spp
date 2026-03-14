<?php

namespace App\Models\Presupuesto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaGastoTrimestral extends Model
{
    protected $table = 'metas_gasto_trimestral';

    protected $fillable = [
        'partida_presupuestal_id',
        'trimestre',
        'monto_programado',
        'justificacion',
    ];

    protected function casts(): array
    {
        return [
            'trimestre' => 'integer',
            'monto_programado' => 'decimal:2',
        ];
    }

    public function partida(): BelongsTo
    {
        return $this->belongsTo(PartidaPresupuestal::class, 'partida_presupuestal_id');
    }
}
