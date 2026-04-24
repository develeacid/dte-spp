<?php

namespace App\Models\Evaluation;

use App\Enums\SemaforoAsm;
use App\Enums\StatusAsm;
use App\Enums\TipoAccionAsm;
use App\Enums\TipoPlazoAsm;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Asm extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'asms';

    protected $fillable = [
        'programa_presupuestario_id',
        'evaluacion_id',
        'descripcion_aspecto',
        'accion_mejora',
        'tipo_plazo',
        'tipo_accion',
        'responsable_id',
        'area_responsable',
        'fecha_compromiso',
        'fecha_cumplimiento',
        'porcentaje_avance',
        'observacion_ultimo_avance',
        'status',
        'evidencia_url',
    ];

    protected $casts = [
        'fecha_compromiso' => 'date',
        'fecha_cumplimiento' => 'date',
        'status' => StatusAsm::class,
        'tipo_plazo' => TipoPlazoAsm::class,
        'tipo_accion' => TipoAccionAsm::class,
        'porcentaje_avance' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('asm')
            ->dontSubmitEmptyLogs();
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(EvaluacionPrograma::class, 'evaluacion_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function getSemaforoAttribute(): SemaforoAsm
    {
        return SemaforoAsm::calcular($this->fecha_compromiso, $this->status);
    }

    public function scopeVigentes($query)
    {
        return $query->where('status', '!=', StatusAsm::CUMPLIDO->value);
    }

    public function scopePorPrograma($query, int $programaId)
    {
        return $query->where('programa_presupuestario_id', $programaId);
    }
}
