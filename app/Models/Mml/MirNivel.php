<?php

namespace App\Models\Mml;

use App\Enums\TipoNivelMir;
use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MirNivel extends Model
{
    protected $table = 'mir_niveles';

    protected $fillable = [
        'programa_presupuestario_id', 'tipo_nivel', 'componente_id',
        'resumen_narrativo', 'supuestos', 'arbol_nodo_id', 'orden',
        'ped_objetivo_estrategico_id', 'programa_derivado_objetivo_id',
        'ped_linea_accion_id', 'team_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo_nivel' => TipoNivelMir::class,
            'orden' => 'integer',
        ];
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
        return $this->belongsTo(\App\Models\Team::class);
    }
}
