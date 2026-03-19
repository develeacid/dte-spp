<?php

namespace App\Models\Tracking;

use App\Models\Mml\IndicadorVariable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvanceVariable extends Model
{
    protected $fillable = [
        'avance_id', 'indicador_variable_id', 'valor', 'valor_acumulado',
        'synced_from_geobase', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:4',
            'valor_acumulado' => 'decimal:4',
            'synced_from_geobase' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function avance(): BelongsTo
    {
        return $this->belongsTo(Avance::class);
    }

    public function indicadorVariable(): BelongsTo
    {
        return $this->belongsTo(IndicadorVariable::class);
    }
}
