<?php

namespace App\Models\Mml;

use App\Models\Tracking\Avance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MetaPeriodo extends Model
{
    protected $table = 'metas_periodo';

    protected $fillable = [
        'indicador_id',
        'periodo',
        'meta_periodo',
        'ejercicio_fiscal',
        'activo',
        'fecha_apertura',
        'fecha_cierre',
    ];

    protected function casts(): array
    {
        return [
            'periodo' => 'integer',
            'meta_periodo' => 'decimal:4',
            'ejercicio_fiscal' => 'integer',
            'activo' => 'boolean',
            'fecha_apertura' => 'date',
            'fecha_cierre' => 'date',
        ];
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class);
    }

    public function avance(): HasOne
    {
        return $this->hasOne(Avance::class);
    }
}
