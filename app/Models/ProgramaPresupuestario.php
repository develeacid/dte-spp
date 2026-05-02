<?php

namespace App\Models;

use App\Enums\EstadoPrograma;
use App\Enums\OrigenPrograma;
use App\Models\Juridico\DocumentoNormativo;
use App\Models\Juridico\SustentoLegalPrograma;
use App\Models\Juridico\ValidacionJuridicaPrograma;
use App\Models\Mml\Alternativa;
use App\Models\Mml\Arbol;
use App\Models\Mml\PoblacionPrograma;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Services\GeoBase\GeoBaseClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramaPresupuestario extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'clave',
        'team_id',
        'ejercicio_fiscal',
        'origen',
        'estado',
        'planeacion_completada_at',
        'created_by',
        'padron_geobase_activo',
    ];

    protected function casts(): array
    {
        return [
            'ejercicio_fiscal' => 'integer',
            'origen' => OrigenPrograma::class,
            'estado' => EstadoPrograma::class,
            'planeacion_completada_at' => 'datetime',
            'padron_geobase_activo' => 'boolean',
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
        return $this->hasMany(Arbol::class, 'programa_presupuestario_id');
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
        return $this->hasMany(Alternativa::class, 'programa_presupuestario_id');
    }

    public function mirNiveles(): HasMany
    {
        return $this->hasMany(Mml\MirNivel::class);
    }

    public function mirVersiones(): HasMany
    {
        return $this->hasMany(Mml\MirVersion::class);
    }

    public function poblacion(): HasOne
    {
        return $this->hasOne(PoblacionPrograma::class, 'programa_id');
    }

    public function partidasPresupuestales(): HasMany
    {
        return $this->hasMany(PartidaPresupuestal::class);
    }

    public function sustentosLegales(): HasMany
    {
        return $this->hasMany(SustentoLegalPrograma::class);
    }

    public function documentosNormativos(): HasMany
    {
        return $this->hasMany(DocumentoNormativo::class);
    }

    public function validacionJuridica(): HasOne
    {
        return $this->hasOne(ValidacionJuridicaPrograma::class)
            ->where('ejercicio_fiscal', config('presupuesto.ejercicio_default'));
    }

    // --- GeoBase ---

    /**
     * dte-spp is the source of truth for program identity. This program is
     * "linked" to GeoBase when its padron has been provisioned there
     * (via the geobase:register-program command). The id used on the wire
     * is always the local programa.id.
     */
    public function hasGeoBaseLink(): bool
    {
        return (bool) $this->padron_geobase_activo;
    }

    public function getGeoBaseCoverage(): ?array
    {
        if (! $this->hasGeoBaseLink()) {
            return null;
        }

        return app(GeoBaseClient::class)
            ->getProgramCoverage($this->id);
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
