<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PndObjetivo extends Model
{
    protected $fillable = [
        'pnd_eje_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'pnd_eje_id' => 'integer',
        ];
    }

    public function eje(): BelongsTo
    {
        return $this->belongsTo(PndEje::class, 'pnd_eje_id');
    }

    public function estrategias(): HasMany
    {
        return $this->hasMany(PndEstrategia::class, 'pnd_objetivo_id');
    }
}
