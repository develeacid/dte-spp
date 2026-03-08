<?php

namespace App\Models\Tracking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Desbloqueo extends Model
{
    protected $fillable = [
        'avance_id', 'motivo', 'solicitado_por', 'resuelto_por',
        'estado', 'resolucion', 'resuelto_at',
    ];

    protected function casts(): array
    {
        return [
            'resuelto_at' => 'datetime',
        ];
    }

    public function avance(): BelongsTo
    {
        return $this->belongsTo(Avance::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function resolutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }
}
