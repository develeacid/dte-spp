<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OdsMeta extends Model
{
    protected $fillable = [
        'ods_objetivo_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ods_objetivo_id' => 'integer',
        ];
    }

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(OdsObjetivo::class, 'ods_objetivo_id');
    }

    public function pndObjetivos(): BelongsToMany
    {
        return $this->belongsToMany(
            PndObjetivo::class,
            'alineacion_pnd_ods',
            'ods_meta_id',
            'pnd_objetivo_id'
        )->withTimestamps();
    }
}
