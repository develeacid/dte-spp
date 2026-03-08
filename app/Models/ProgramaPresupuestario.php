<?php

namespace App\Models;

use App\Enums\EstadoPrograma;
use App\Enums\OrigenPrograma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ejercicio_fiscal' => 'integer',
            'origen' => OrigenPrograma::class,
            'estado' => EstadoPrograma::class,
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
