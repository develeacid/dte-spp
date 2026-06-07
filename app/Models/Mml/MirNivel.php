<?php

namespace App\Models\Mml;

use App\Enums\TipoNivelMir;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Support\Mml\Trazabilidad;
use Database\Factories\Mml\MirNivelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MirNivel extends Model
{
    /** @use HasFactory<MirNivelFactory> */
    use HasFactory;

    use LogsActivity;

    protected $table = 'mir_niveles';

    protected $fillable = [
        'programa_presupuestario_id', 'tipo_nivel', 'componente_id',
        'resumen_narrativo', 'supuestos', 'arbol_nodo_id', 'orden',
        'ped_objetivo_estrategico_id', 'programa_derivado_objetivo_id',
        'ped_linea_accion_id', 'team_id',
        'sintaxis_valida', 'sintaxis_observacion',
        'sintaxis_sugerencia', 'sintaxis_validada_at',
    ];

    protected function casts(): array
    {
        return [
            'tipo_nivel' => TipoNivelMir::class,
            'orden' => 'integer',
            'sintaxis_valida' => 'boolean',
            'sintaxis_validada_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'programa_presupuestario_id', 'tipo_nivel', 'resumen_narrativo',
                'supuestos', 'orden', 'ped_objetivo_estrategico_id',
                'ped_linea_accion_id', 'team_id',
                'sintaxis_valida', 'sintaxis_observacion',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "MirNivel {$eventName}");
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(self::class, 'componente_id');
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(self::class, 'componente_id')->orderBy('orden');
    }

    public function nodoOrigen(): BelongsTo
    {
        return $this->belongsTo(ArbolNodo::class, 'arbol_nodo_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function indicadores(): HasMany
    {
        return $this->hasMany(Indicador::class)->orderBy('orden');
    }

    public function pedObjetivoEstrategico(): BelongsTo
    {
        return $this->belongsTo(PedObjetivoEstrategico::class);
    }

    public function pedLineaAccion(): BelongsTo
    {
        return $this->belongsTo(PedLineaAccion::class);
    }

    public function trazabilidad(): Trazabilidad
    {
        return Trazabilidad::deNivel($this);
    }
}
