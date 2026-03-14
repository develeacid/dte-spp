<?php

namespace App\Models\Juridico;

use App\Enums\EstadoValidacionJuridica;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ValidacionJuridicaPrograma extends Model
{
    use LogsActivity;

    protected $table = 'validacion_juridica_programa';

    protected $fillable = [
        'programa_presupuestario_id', 'ejercicio_fiscal', 'estado',
        'tiene_facultad_ur', 'tiene_mandato_gasto', 'tiene_rop',
        'observaciones', 'validado_por', 'validado_at',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoValidacionJuridica::class,
            'tiene_facultad_ur' => 'boolean',
            'tiene_mandato_gasto' => 'boolean',
            'tiene_rop' => 'boolean',
            'validado_at' => 'datetime',
            'ejercicio_fiscal' => 'integer',
        ];
    }

    // --- Relaciones ---

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    // --- Accessors ---

    public function getChecklistCompletoAttribute(): bool
    {
        $base = $this->tiene_facultad_ur && $this->tiene_mandato_gasto;
        if ($this->tiene_rop === null) {
            return $base;
        }

        return $base && $this->tiene_rop;
    }

    public function getItemsPendientesAttribute(): array
    {
        $pendientes = [];
        if (! $this->tiene_facultad_ur) {
            $pendientes[] = 'Facultad de la UR';
        }
        if (! $this->tiene_mandato_gasto) {
            $pendientes[] = 'Mandato de gasto';
        }
        if ($this->tiene_rop === false) {
            $pendientes[] = 'Reglas de Operación';
        }

        return $pendientes;
    }

    // --- Auditoría ---

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['estado', 'tiene_facultad_ur', 'tiene_mandato_gasto', 'tiene_rop', 'validado_por'])
            ->logOnlyDirty();
    }
}
