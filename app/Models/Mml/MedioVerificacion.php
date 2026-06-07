<?php

namespace App\Models\Mml;

use App\Enums\FrecuenciaMedicion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MedioVerificacion extends Model
{
    protected $table = 'medios_verificacion';

    protected $fillable = [
        'indicador_id', 'nombre', 'descripcion', 'fuente', 'tipo_fuente',
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

    public function cremaValidacion(): HasOne
    {
        return $this->hasOne(CremaValidacionMv::class, 'medio_verificacion_id');
    }

    /**
     * Normaliza un valor de frecuencia legacy (texto libre) al value canónico
     * del enum FrecuenciaMedicion si coincide tras trim/lowercase.
     * Devuelve null cuando el valor no es normalizable (queda intacto en BD).
     */
    public static function normalizarFrecuencia(string $valor): ?string
    {
        $normalizado = strtolower(trim($valor));

        return FrecuenciaMedicion::tryFrom($normalizado)?->value;
    }
}
