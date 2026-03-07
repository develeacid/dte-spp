<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramaDerivadoObjetivo extends Model
{
    protected $table = 'programas_derivados_objetivos';

    protected $fillable = [
        'programa_derivado_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'programa_derivado_id' => 'integer',
        ];
    }

    // ============================================
    // Relaciones
    // ============================================

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaDerivado::class, 'programa_derivado_id');
    }

    public function plan()
    {
        return $this->programa->plan;
    }

    // ============================================
    // Accessors
    // ============================================

    /**
     * Retorna clave completa con prefijo del programa.
     */
    public function getClaveCompletaAttribute(): string
    {
        return $this->programa->prefijoClave() . '.' . $this->clave;
    }
}
