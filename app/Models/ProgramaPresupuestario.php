<?php

namespace App\Models;

use App\Enums\EstadoPrograma;
use App\Enums\OrigenPrograma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramaPresupuestario extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'clave',
        'team_id',
        'ejercicio_fiscal',
        'origen',
        'estado',
        'planeacion_completada_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ejercicio_fiscal' => 'integer',
            'origen' => OrigenPrograma::class,
            'estado' => EstadoPrograma::class,
            'planeacion_completada_at' => 'datetime',
        ];
    }

    // --- Relaciones ---

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function equipos()
    {
        return $this->belongsToMany(Team::class, 'programa_team')
                    ->withPivot('rol')
                    ->withTimestamps();
    }

    public function arboles(): HasMany
    {
        return $this->hasMany(\App\Models\Mml\Arbol::class, 'programa_presupuestario_id');
    }

    public function arbolProblema()
    {
        return $this->arboles()->where('tipo', 'problema')->first();
    }

    public function arbolObjetivos()
    {
        return $this->arboles()->where('tipo', 'objetivos')->first();
    }

    public function alternativas(): HasMany
    {
        return $this->hasMany(\App\Models\Mml\Alternativa::class, 'programa_presupuestario_id');
    }

    public function mirNiveles(): HasMany
    {
        return $this->hasMany(Mml\MirNivel::class);
    }

    public function mirVersiones(): HasMany
    {
        return $this->hasMany(Mml\MirVersion::class);
    }

    // --- Scopes ---

    public function scopeParaTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeEjercicio(Builder $query, int $anio): Builder
    {
        return $query->where('ejercicio_fiscal', $anio);
    }
}
