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
use App\Models\Presupuesto\ClasificacionFuncional;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Services\GeoBase\GeoBaseClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        // Clave presupuestal canónica SEFIP/CONAC (nivel programa).
        'grupo',
        'unidad_responsable',
        'unidad_ejecutora',
        'programa_clave',
        'subprograma',
        'proyecto',
        'actividad',
        'finalidad_id',
        'funcion_id',
        'subfuncion_id',
        'clave_sefip',
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

    /** UR administradora del programa (temario C-036): el team con rol pivote 'coordinadora'. */
    public function urAdministradora(): ?Team
    {
        return $this->equipos()->wherePivot('rol', 'coordinadora')->first();
    }

    public function arboles(): HasMany
    {
        return $this->hasMany(Arbol::class, 'programa_presupuestario_id');
    }

    // --- Clasificación Funcional CONAC (clave presupuestal canónica) ---

    public function finalidad(): BelongsTo
    {
        return $this->belongsTo(ClasificacionFuncional::class, 'finalidad_id');
    }

    public function funcion(): BelongsTo
    {
        return $this->belongsTo(ClasificacionFuncional::class, 'funcion_id');
    }

    public function subfuncion(): BelongsTo
    {
        return $this->belongsTo(ClasificacionFuncional::class, 'subfuncion_id');
    }

    /**
     * Campos administrativos + programáticos que componen la clave canónica nivel programa.
     */
    private const SEGMENTOS_CLAVE = [
        'grupo', 'unidad_responsable', 'unidad_ejecutora',
        'programa_clave', 'subprograma', 'proyecto', 'actividad',
    ];

    /**
     * Clave presupuestaria canónica SEFIP (bloques Administrativa + Programática, 17 dígitos).
     * NULL si falta algún segmento administrativo/programático.
     */
    protected function clavePresupuestalCanonica(): Attribute
    {
        return Attribute::make(get: fn () => $this->componerClaveCanonica());
    }

    /**
     * Composición documentada y configurable de la clave canónica.
     * Administrativa: Grupo(1) UR(2) UE(3). Programática: Programa(3) Subprog(2) Proyecto(3) Actividad(3).
     */
    private function componerClaveCanonica(): ?string
    {
        if (! $this->claveCanonicaCompleta()) {
            return null;
        }

        $administrativa = sprintf('%01d%02d%03d', $this->grupo, $this->unidad_responsable, $this->unidad_ejecutora);
        $programatica = sprintf('%03d%02d%03d%03d', $this->programa_clave, $this->subprograma, $this->proyecto, $this->actividad);

        return $administrativa.$programatica;
    }

    /** True cuando los 7 segmentos administrativos/programáticos están capturados (0 es válido). */
    public function claveCanonicaCompleta(): bool
    {
        foreach (self::SEGMENTOS_CLAVE as $segmento) {
            if ($this->{$segmento} === null) {
                return false;
            }
        }

        return true;
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
