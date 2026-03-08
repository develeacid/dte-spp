<?php

namespace App\Models;

use App\Traits\HasEmbedding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PndObjetivo extends Model
{
    use HasEmbedding;

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

    public function pedObjetivosEstrategicos(): BelongsToMany
    {
        return $this->belongsToMany(
            PedObjetivoEstrategico::class,
            'alineacion_ped_pnd',
            'pnd_objetivo_id',
            'ped_objetivo_estrategico_id'
        )->withTimestamps();
    }

    public function odsMetas(): BelongsToMany
    {
        return $this->belongsToMany(
            OdsMeta::class,
            'alineacion_pnd_ods',
            'pnd_objetivo_id',
            'ods_meta_id'
        )->withTimestamps();
    }
}
