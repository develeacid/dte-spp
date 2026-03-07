<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedObjetivoEstrategico extends Model
{
    protected $table = 'ped_objetivos_estrategicos';

    protected $fillable = [
        'ped_tema_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ped_tema_id' => 'integer',
        ];
    }

    public function tema(): BelongsTo
    {
        return $this->belongsTo(PedTema::class, 'ped_tema_id');
    }

    public function eje()
    {
        return $this->tema->eje;
    }

    public function plan()
    {
        return $this->tema->eje->plan;
    }

    public function estrategias(): HasMany
    {
        return $this->hasMany(PedEstrategia::class, 'ped_objetivo_estrategico_id');
    }

    public function lineasAccion()
    {
        return $this->hasManyThrough(PedLineaAccion::class, PedEstrategia::class);
    }

    public function getClaveCompletaAttribute(): string
    {
        return $this->tema->clave_completa . '.' . $this->clave;
    }
}
