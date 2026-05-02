<?php

namespace App\Models;

use App\Traits\HasEmbedding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedEstrategia extends Model
{
    use HasEmbedding;

    protected $fillable = [
        'ped_objetivo_estrategico_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ped_objetivo_estrategico_id' => 'integer',
        ];
    }

    public function objetivoEstrategico(): BelongsTo
    {
        return $this->belongsTo(PedObjetivoEstrategico::class, 'ped_objetivo_estrategico_id');
    }

    public function tema()
    {
        return $this->objetivoEstrategico->tema;
    }

    public function eje()
    {
        return $this->objetivoEstrategico->tema->eje;
    }

    public function plan()
    {
        return $this->objetivoEstrategico->tema->eje->plan;
    }

    public function lineasAccion(): HasMany
    {
        return $this->hasMany(PedLineaAccion::class, 'ped_estrategia_id');
    }

    public function getClaveCompletaAttribute(): string
    {
        return $this->objetivoEstrategico->clave_completa.'.'.$this->clave;
    }
}
