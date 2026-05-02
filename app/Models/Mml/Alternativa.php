<?php

namespace App\Models\Mml;

use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Alternativa extends Model
{
    protected $fillable = [
        'programa_presupuestario_id',
        'nombre',
        'seleccionada',
        'justificacion_seleccion',
    ];

    protected function casts(): array
    {
        return [
            'seleccionada' => 'boolean',
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function nodos(): BelongsToMany
    {
        return $this->belongsToMany(ArbolNodo::class, 'alternativa_nodo')
            ->withTimestamps();
    }
}
