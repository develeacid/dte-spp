<?php

namespace App\Models\Tracking;

use App\Enums\EstadoAvance;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Avance extends Model
{
    protected $fillable = [
        'meta_periodo_id', 'indicador_id', 'resultado',
        'semaforo_calculado', 'semaforo_ajustado',
        'justificacion_ia', 'justificacion_final',
        'estado', 'historial_observaciones', 'congelado_at', 'capturado_por',
    ];

    protected function casts(): array
    {
        return [
            'resultado' => 'decimal:4',
            'estado' => EstadoAvance::class,
            'historial_observaciones' => 'array',
            'congelado_at' => 'datetime',
        ];
    }

    public function estaCongelado(): bool
    {
        return $this->congelado_at !== null;
    }

    public function metaPeriodo(): BelongsTo
    {
        return $this->belongsTo(MetaPeriodo::class);
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class);
    }

    public function variables(): HasMany
    {
        return $this->hasMany(AvanceVariable::class);
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(AvanceEvidencia::class);
    }

    public function desbloqueos(): HasMany
    {
        return $this->hasMany(Desbloqueo::class);
    }

    public function capturador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }
}
