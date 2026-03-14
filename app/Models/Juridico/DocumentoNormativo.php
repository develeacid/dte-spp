<?php

namespace App\Models\Juridico;

use App\Enums\TipoDocumentoNormativo;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DocumentoNormativo extends Model
{
    use LogsActivity;

    protected $table = 'documentos_normativos';

    protected $fillable = [
        'programa_presupuestario_id', 'tipo_documento', 'nombre',
        'archivo_path', 'archivo_hash', 'archivo_size',
        'fecha_publicacion', 'fecha_vigencia',
        'verificado', 'verificado_por', 'verificado_at',
        'registrado_por', 'team_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo_documento' => TipoDocumentoNormativo::class,
            'fecha_publicacion' => 'date',
            'fecha_vigencia' => 'date',
            'verificado' => 'boolean',
            'verificado_at' => 'datetime',
        ];
    }

    // --- Relaciones ---

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function verificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
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

    public function scopeVerificados($query)
    {
        return $query->where('verificado', true);
    }

    public function scopeRop($query)
    {
        return $query->where('tipo_documento', TipoDocumentoNormativo::REGLAS_OPERACION);
    }

    // --- Accessors ---

    public function getVigenteAttribute(): bool
    {
        if (! $this->fecha_vigencia) {
            return true;
        }

        return $this->fecha_vigencia->isFuture();
    }

    public function getArchivoSizeHumanoAttribute(): string
    {
        $bytes = $this->archivo_size ?? 0;
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }

    // --- Auditoría ---

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'tipo_documento', 'verificado', 'verificado_por'])
            ->logOnlyDirty();
    }
}
