<?php

namespace App\Models\Presupuesto;

use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Snapshot persistido del Informe de Avance Físico-Financiero por
 * programa + ejercicio + trimestre. Una vez firmado es inmutable
 * (el hash sha256 garantiza la integridad).
 */
class Iaff extends Model
{
    use HasFactory;

    protected $table = 'iaff';

    protected $fillable = [
        'programa_id',
        'ejercicio_fiscal',
        'trimestre',
        'snapshot_payload',
        'hash_sha256',
        'generado_en',
        'generado_por',
        'firmado_en',
        'firmado_por',
    ];

    protected function casts(): array
    {
        return [
            'ejercicio_fiscal' => 'integer',
            'trimestre' => 'integer',
            'snapshot_payload' => 'array',
            'generado_en' => 'datetime',
            'firmado_en' => 'datetime',
        ];
    }

    public function estaFirmado(): bool
    {
        return $this->firmado_en !== null;
    }

    public function scopeFirmados(Builder $query): Builder
    {
        return $query->whereNotNull('firmado_en');
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_id');
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    public function firmadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'firmado_por');
    }
}
