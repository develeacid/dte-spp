<?php

namespace App\Models\Mml;

use App\Enums\TipoNodo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArbolNodo extends Model
{
    protected $table = 'arbol_nodos';

    protected $fillable = [
        'arbol_id',
        'parent_id',
        'tipo_nodo',
        'descripcion',
        'nodo_origen_id',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'tipo_nodo' => TipoNodo::class,
            'orden' => 'integer',
        ];
    }

    public function arbol(): BelongsTo
    {
        return $this->belongsTo(Arbol::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('orden');
    }

    public function nodoOrigen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'nodo_origen_id');
    }

    public function nodosDerivados(): HasMany
    {
        return $this->hasMany(self::class, 'nodo_origen_id');
    }
}
