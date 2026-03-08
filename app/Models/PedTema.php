<?php

namespace App\Models;

use App\Traits\HasEmbedding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedTema extends Model
{
    use HasEmbedding;

    protected $fillable = [
        'ped_eje_id',
        'numero',
        'nombre',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ped_eje_id' => 'integer',
        ];
    }

    public function eje(): BelongsTo
    {
        return $this->belongsTo(PedEje::class, 'ped_eje_id');
    }

    public function objetivosEstrategicos(): HasMany
    {
        return $this->hasMany(PedObjetivoEstrategico::class, 'ped_tema_id');
    }

    public function estrategias()
    {
        return $this->hasManyThrough(PedEstrategia::class, PedObjetivoEstrategico::class);
    }

    public function lineasAccion()
    {
        return $this->hasManyThrough(
            PedLineaAccion::class,
            PedEstrategia::class,
            'id', // FK en objetivos (intermedio 1)
            'ped_estrategia_id', // FK en líneas
            'id', // PK local
            'id'  // PK intermedio 2
        )->join('ped_objetivos_estrategicos', 'ped_estrategias.ped_objetivo_estrategico_id', '=', 'ped_objetivos_estrategicos.id')
         ->where('ped_objetivos_estrategicos.ped_tema_id', $this->id);
    }

    // Accessor para clave completa
    public function getClaveCompletaAttribute(): string
    {
        return $this->eje->numero . '.' . $this->numero;
    }
}
