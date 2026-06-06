<?php

namespace App\Models\Mml;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedioVerificacion extends Model
{
    protected $table = 'medios_verificacion';

    protected $fillable = [
        'indicador_id', 'nombre', 'descripcion', 'fuente',
        'organismo', 'url', 'frecuencia', 'orden',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
        ];
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class);
    }
}
