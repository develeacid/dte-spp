<?php

namespace App\Models\Mml;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MirSupuesto extends Model
{
    protected $table = 'mir_supuestos';

    protected $fillable = [
        'mir_nivel_id',
        'descripcion',
        'es_externo',
        'es_relevante',
        'probabilidad_razonable',
        'orden',
    ];

    protected $casts = [
        'es_externo' => 'boolean',
        'es_relevante' => 'boolean',
        'probabilidad_razonable' => 'boolean',
        'orden' => 'integer',
    ];

    public function mirNivel(): BelongsTo
    {
        return $this->belongsTo(MirNivel::class, 'mir_nivel_id');
    }

    /**
     * Supuesto válido según el temario (C-075): externo + relevante +
     * razonablemente probable de cumplirse.
     */
    public function esValido(): bool
    {
        return $this->es_externo && $this->es_relevante && $this->probabilidad_razonable;
    }
}
