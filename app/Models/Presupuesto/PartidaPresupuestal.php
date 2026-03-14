<?php

namespace App\Models\Presupuesto;

use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PartidaPresupuestal extends Model
{
    use LogsActivity;

    protected $table = 'partidas_presupuestales';

    protected $fillable = [
        'programa_presupuestario_id',
        'clave_partida',
        'descripcion',
        'monto_aprobado',
        'monto_modificado',
        'ejercicio_fiscal',
        'team_id',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'monto_aprobado' => 'decimal:2',
            'monto_modificado' => 'decimal:2',
            'ejercicio_fiscal' => 'integer',
        ];
    }

    // --- Relaciones ---

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function avancesFinancieros(): HasMany
    {
        return $this->hasMany(AvanceFinanciero::class);
    }

    public function metasGasto(): HasMany
    {
        return $this->hasMany(MetaGastoTrimestral::class);
    }

    // --- Scopes ---

    public function scopeParaTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeParaEjercicio($query, int $ejercicio)
    {
        return $query->where('ejercicio_fiscal', $ejercicio);
    }

    // --- Accessors ---

    public function getMontoEfectivoAttribute(): float
    {
        return $this->monto_modificado ?? $this->monto_aprobado;
    }

    public function getPorcentajeEjercidoAttribute(): float
    {
        $efectivo = $this->monto_efectivo;
        if ($efectivo <= 0) {
            return 0;
        }

        $pagado = $this->avancesFinancieros->sum('monto_pagado');

        return round(($pagado / $efectivo) * 100, 2);
    }

    // --- Auditoría ---

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['clave_partida', 'monto_aprobado', 'monto_modificado'])
            ->logOnlyDirty();
    }
}
