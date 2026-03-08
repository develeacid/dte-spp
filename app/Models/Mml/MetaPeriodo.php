<?php

namespace App\Models\Mml;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function avance(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Tracking\Avance::class);
    }
}
