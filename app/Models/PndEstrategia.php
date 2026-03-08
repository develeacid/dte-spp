<?php

namespace App\Models;

use App\Traits\HasEmbedding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PndEstrategia extends Model
{
    use HasEmbedding;

    protected $fillable = [
        'pnd_objetivo_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'pnd_objetivo_id' => 'integer',
        ];
    }

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(PndObjetivo::class, 'pnd_objetivo_id');
    }
}
