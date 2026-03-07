<?php

namespace App\Models;

use App\Enums\TipoProgramaDerivado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramaDerivado extends Model
{
    protected $table = 'programas_derivados';

    protected $fillable = [
        'ped_plan_id',
        'nombre',
        'descripcion',
        'tipo',
    ];

    protected function casts(): array
    {
        return [
            'ped_plan_id' => 'integer',
            'tipo' => TipoProgramaDerivado::class,
        ];
    }

    // ============================================
    // Relaciones
    // ============================================

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PedPlan::class, 'ped_plan_id');
    }

    public function objetivos(): HasMany
    {
        return $this->hasMany(ProgramaDerivadoObjetivo::class, 'programa_derivado_id');
    }

    // ============================================
    // Scopes
    // ============================================

    public function scopeSectoriales($query)
    {
        return $query->where('tipo', TipoProgramaDerivado::SECTORIAL);
    }

    public function scopeEspeciales($query)
    {
        return $query->where('tipo', TipoProgramaDerivado::ESPECIAL);
    }

    public function scopeInstitucionales($query)
    {
        return $query->where('tipo', TipoProgramaDerivado::INSTITUCIONAL);
    }

    public function scopeRegionales($query)
    {
        return $query->where('tipo', TipoProgramaDerivado::REGIONAL);
    }

    // ============================================
    // Métodos auxiliares
    // ============================================

    /**
     * Retorna el prefijo de clave según el tipo.
     * Útil para generar claves de objetivos automáticamente.
     */
    public function prefijoClave(): string
    {
        return match($this->tipo) {
            TipoProgramaDerivado::SECTORIAL => 'OS',
            TipoProgramaDerivado::ESPECIAL => 'OE',
            TipoProgramaDerivado::INSTITUCIONAL => 'OI',
            TipoProgramaDerivado::REGIONAL => 'OR',
        };
    }
}
