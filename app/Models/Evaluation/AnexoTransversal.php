<?php

namespace App\Models\Evaluation;

use App\Models\Mml\Indicador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AnexoTransversal extends Model
{
    protected $table = 'anexos_transversales';

    protected $fillable = [
        'nombre',
        'clave',
        'descripcion',
        'activo',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function indicadores(): BelongsToMany
    {
        return $this->belongsToMany(Indicador::class, 'indicador_anexo_transversal');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
