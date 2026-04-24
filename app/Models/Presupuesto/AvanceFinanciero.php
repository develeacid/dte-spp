<?php

namespace App\Models\Presupuesto;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AvanceFinanciero extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'avances_financieros';

    protected $fillable = [
        'partida_presupuestal_id',
        'trimestre',
        'monto_comprometido',
        'monto_devengado',
        'monto_pagado',
        'registrado_por',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'trimestre' => 'integer',
            'monto_comprometido' => 'decimal:2',
            'monto_devengado' => 'decimal:2',
            'monto_pagado' => 'decimal:2',
        ];
    }

    // --- Relaciones ---

    public function partida(): BelongsTo
    {
        return $this->belongsTo(PartidaPresupuestal::class, 'partida_presupuestal_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    // --- Auditoría ---

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['monto_comprometido', 'monto_devengado', 'monto_pagado'])
            ->logOnlyDirty();
    }
}
