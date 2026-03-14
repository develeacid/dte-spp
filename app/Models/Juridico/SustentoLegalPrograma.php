<?php

namespace App\Models\Juridico;

use App\Enums\NivelJerarquiaLegal;
use App\Enums\TipoSustentoLegal;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SustentoLegalPrograma extends Model
{
    use LogsActivity;

    protected $table = 'sustento_legal_programa';

    protected $fillable = [
        'programa_presupuestario_id', 'tipo', 'catalogo_ordenamiento_id',
        'ordenamiento', 'articulo', 'descripcion', 'nivel_jerarquia',
        'vigente', 'registrado_por', 'validado_por', 'validado_at', 'team_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoSustentoLegal::class,
            'nivel_jerarquia' => NivelJerarquiaLegal::class,
            'vigente' => 'boolean',
            'validado_at' => 'datetime',
        ];
    }

    // --- Relaciones ---

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function catalogoOrdenamiento(): BelongsTo
    {
        return $this->belongsTo(CatalogoOrdenamiento::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    // --- Scopes ---

    public function scopeParaTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeVigentes($query)
    {
        return $query->where('vigente', true);
    }

    public function scopePorTipo($query, TipoSustentoLegal $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // --- Accessors ---

    public function getCitaCompletaAttribute(): string
    {
        $cita = $this->ordenamiento;
        if ($this->articulo) {
            $cita .= ', '.$this->articulo;
        }

        return $cita;
    }

    // --- Auditoría ---

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipo', 'ordenamiento', 'articulo', 'vigente', 'validado_por'])
            ->logOnlyDirty();
    }
}
