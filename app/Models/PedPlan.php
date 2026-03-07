<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedPlan extends Model
{
    protected $table = 'ped_planes';

    protected $fillable = [
        'nombre',
        'nivel_gobierno',
        'periodo_inicio',
        'periodo_fin',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'integer',
            'periodo_fin' => 'integer',
            'activo' => 'boolean',
        ];
    }

    // ============================================
    // Relaciones
    // ============================================

    public function ejes(): HasMany
    {
        return $this->hasMany(PedEje::class, 'ped_plan_id');
    }

    // Relaciones through (acceso directo a niveles profundos)
    public function temas()
    {
        return $this->hasManyThrough(PedTema::class, PedEje::class);
    }

    public function objetivosEstrategicos()
    {
        return $this->hasManyThrough(
            PedObjetivoEstrategico::class,
            PedEje::class,
            'ped_plan_id',       // FK en ped_ejes
            'id',                // PK en ped_ejes
            'id',                // PK en ped_planes
            'id'                 // FK en ped_temas (se usa join intermedio)
        );
    }

    // ============================================
    // Scopes
    // ============================================

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    // ============================================
    // Métodos auxiliares
    // ============================================

    /**
     * Activa este plan y desactiva todos los demás.
     * Respeta el constraint único parcial de PostgreSQL.
     */
    public function activar(): void
    {
        // Desactivar todos los planes primero
        static::query()->update(['activo' => false]);

        // Activar este plan
        $this->update(['activo' => true]);
    }

    /**
     * Obtiene el plan activo actual.
     */
    public static function planActivo(): ?self
    {
        return static::where('activo', true)->first();
    }
}
