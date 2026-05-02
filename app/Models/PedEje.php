<?php

namespace App\Models;

use App\Traits\HasEmbedding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PedEje extends Model
{
    use HasEmbedding;
    use LogsActivity;

    protected $fillable = [
        'ped_plan_id',
        'numero',
        'nombre',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ped_plan_id' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ped_plan_id', 'numero', 'nombre', 'descripcion'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "PedEje {$eventName}");
    }

    // ============================================
    // Relaciones
    // ============================================

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PedPlan::class, 'ped_plan_id');
    }

    public function temas(): HasMany
    {
        return $this->hasMany(PedTema::class, 'ped_eje_id');
    }

    public function objetivosEstrategicos()
    {
        return $this->hasManyThrough(PedObjetivoEstrategico::class, PedTema::class);
    }

    public function estrategias()
    {
        return $this->hasManyThrough(
            PedEstrategia::class,
            PedObjetivoEstrategico::class,
            'id', // FK en objetivos (intermedio)
            'ped_objetivo_estrategico_id', // FK en estrategias
            'id', // PK local
            'id'  // PK intermedio
        )->join('ped_temas', 'ped_objetivos_estrategicos.ped_tema_id', '=', 'ped_temas.id')
            ->where('ped_temas.ped_eje_id', $this->id);
    }

    public function lineasAccion()
    {
        // 4 niveles de profundidad - mejor usar queries separados
        return $this->hasManyThrough(PedLineaAccion::class, PedEstrategia::class);
    }
}
