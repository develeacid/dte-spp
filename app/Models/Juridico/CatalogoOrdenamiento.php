<?php

namespace App\Models\Juridico;

use App\Enums\NivelJerarquiaLegal;
use Illuminate\Database\Eloquent\Model;

class CatalogoOrdenamiento extends Model
{
    protected $table = 'catalogo_ordenamientos';

    protected $fillable = ['nombre', 'nivel_jerarquia', 'abreviatura', 'activo', 'orden'];

    protected function casts(): array
    {
        return [
            'nivel_jerarquia' => NivelJerarquiaLegal::class,
            'activo' => 'boolean',
        ];
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorNivel($query, NivelJerarquiaLegal $nivel)
    {
        return $query->where('nivel_jerarquia', $nivel);
    }
}
