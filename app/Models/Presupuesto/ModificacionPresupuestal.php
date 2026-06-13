<?php

namespace App\Models\Presupuesto;

use App\Enums\TipoModificacionPresupuestal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adecuación presupuestaria (ampliación/reducción) sobre una partida (V2-E5).
 */
class ModificacionPresupuestal extends Model
{
    protected $table = 'modificaciones_presupuestales';

    protected $fillable = [
        'partida_presupuestal_id',
        'tipo',
        'monto',
        'fecha',
        'oficio',
        'justificacion',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoModificacionPresupuestal::class,
            'monto' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    public function partida(): BelongsTo
    {
        return $this->belongsTo(PartidaPresupuestal::class, 'partida_presupuestal_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
