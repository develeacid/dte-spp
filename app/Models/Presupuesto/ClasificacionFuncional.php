<?php

namespace App\Models\Presupuesto;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo CONAC — Clasificación Funcional del Gasto.
 * Jerarquía Finalidad → Función → Subfunción vía self-FK (padre_id).
 */
class ClasificacionFuncional extends Model
{
    protected $table = 'clasificacion_funcional';

    protected $fillable = [
        'nivel',
        'clave',
        'nombre',
        'padre_id',
    ];

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(self::class, 'padre_id');
    }

    public function scopeFinalidades(Builder $query): Builder
    {
        return $query->where('nivel', 'finalidad');
    }

    public function scopeFunciones(Builder $query): Builder
    {
        return $query->where('nivel', 'funcion');
    }

    public function scopeSubfunciones(Builder $query): Builder
    {
        return $query->where('nivel', 'subfuncion');
    }
}
