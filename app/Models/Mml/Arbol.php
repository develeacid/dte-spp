<?php

namespace App\Models\Mml;

use App\Enums\TipoArbol;
use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Arbol extends Model
{
    protected $table = 'arboles';

    protected $fillable = [
        'programa_presupuestario_id',
        'tipo',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoArbol::class,
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function nodos(): HasMany
    {
        return $this->hasMany(ArbolNodo::class, 'arbol_id');
    }
}
