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
    ];

    protected function casts(): array
    {
        return [
            'periodo' => 'integer',
            'meta_periodo' => 'decimal:4',
            'ejercicio_fiscal' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class);
    }
}
