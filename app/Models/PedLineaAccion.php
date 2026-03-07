<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedLineaAccion extends Model
{
    protected $table = 'ped_lineas_accion';

    protected $fillable = [
        'ped_estrategia_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ped_estrategia_id' => 'integer',
        ];
    }

    public function estrategia(): BelongsTo
    {
        return $this->belongsTo(PedEstrategia::class, 'ped_estrategia_id');
    }

    public function objetivoEstrategico()
    {
        return $this->estrategia->objetivoEstrategico;
    }

    public function tema()
    {
        return $this->estrategia->objetivoEstrategico->tema;
    }

    public function eje()
    {
        return $this->estrategia->objetivoEstrategico->tema->eje;
    }

    public function plan()
    {
        return $this->estrategia->objetivoEstrategico->tema->eje->plan;
    }

    public function getClaveCompletaAttribute(): string
    {
        return $this->estrategia->clave_completa . '.' . $this->clave;
    }
}
