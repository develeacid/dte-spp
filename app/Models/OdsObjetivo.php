<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OdsObjetivo extends Model
{
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

    public function metas(): HasMany
    {
        return $this->hasMany(OdsMeta::class, 'ods_objetivo_id');
    }
}
