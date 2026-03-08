<?php

namespace App\Models;

use App\Traits\HasEmbedding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PndEje extends Model
{
    use HasEmbedding;

    protected $fillable = [
        'numero',
        'nombre',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
        ];
    }

    public function objetivos(): HasMany
    {
        return $this->hasMany(PndObjetivo::class, 'pnd_eje_id');
    }

    public function estrategias(): HasManyThrough
    {
        return $this->hasManyThrough(PndEstrategia::class, PndObjetivo::class);
    }
}
