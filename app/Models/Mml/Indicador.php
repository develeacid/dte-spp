<?php

namespace App\Models\Mml;

use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\SentidoIndicador;
use App\Enums\TipoIndicador;
use App\Models\CatalogoUnidadMedida;
use App\Models\Evaluation\AnexoTransversal;
use App\Models\Tracking\Avance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Indicador extends Model
{
    use LogsActivity;

    protected $table = 'indicadores';

    protected $fillable = [
        'mir_nivel_id', 'nombre', 'formula_texto', 'tipo', 'dimension',
        'frecuencia', 'sentido', 'linea_base', 'meta',
        'rango_verde_min', 'rango_verde_max',
        'rango_amarillo_min', 'rango_amarillo_max',
        'rango_rojo_min', 'rango_rojo_max',
        'unidad_medida_id', 'orden', 'activo_seguimiento',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoIndicador::class,
            'dimension' => DimensionIndicador::class,
            'frecuencia' => FrecuenciaMedicion::class,
            'sentido' => SentidoIndicador::class,
            'linea_base' => 'decimal:4',
            'meta' => 'decimal:4',
            'orden' => 'integer',
            'activo_seguimiento' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'mir_nivel_id', 'nombre', 'formula_texto', 'tipo', 'dimension',
                'frecuencia', 'sentido', 'linea_base', 'meta',
                'rango_verde_min', 'rango_verde_max',
                'rango_amarillo_min', 'rango_amarillo_max',
                'rango_rojo_min', 'rango_rojo_max',
                'unidad_medida_id', 'activo_seguimiento',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Indicador {$eventName}");
    }

    public function mirNivel(): BelongsTo
    {
        return $this->belongsTo(MirNivel::class);
    }

    public function variables(): HasMany
    {
        return $this->hasMany(IndicadorVariable::class)->orderBy('orden');
    }

    public function mediosVerificacion(): HasMany
    {
        return $this->hasMany(MedioVerificacion::class)->orderBy('orden');
    }

    public function cremaaValidacion(): HasOne
    {
        return $this->hasOne(CremaaValidacion::class);
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(CatalogoUnidadMedida::class, 'unidad_medida_id');
    }

    public function metasPeriodo(): HasMany
    {
        return $this->hasMany(MetaPeriodo::class)->orderBy('periodo');
    }

    public function avances(): HasMany
    {
        return $this->hasMany(Avance::class);
    }

    public function anexosTransversales(): BelongsToMany
    {
        return $this->belongsToMany(
            AnexoTransversal::class,
            'indicador_anexo_transversal'
        );
    }
}
