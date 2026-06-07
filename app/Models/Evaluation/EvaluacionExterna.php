<?php

namespace App\Models\Evaluation;

use App\Enums\EstadoEvaluacionExterna;
use App\Enums\TipoEvaluacionExterna;
use App\Models\ProgramaPresupuestario;
use Database\Factories\Evaluation\EvaluacionExternaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @method static EvaluacionExternaFactory factory($count = null, $state = [])
 */
class EvaluacionExterna extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_externas';

    protected $fillable = [
        'programa_presupuestario_id',
        'ejercicio_fiscal',
        'tipo',
        'evaluador_externo',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'evaluacion_programa_id',
    ];

    protected function casts(): array
    {
        return [
            'ejercicio_fiscal' => 'integer',
            'tipo' => TipoEvaluacionExterna::class,
            'estado' => EstadoEvaluacionExterna::class,
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function evaluacionPrograma(): BelongsTo
    {
        return $this->belongsTo(EvaluacionPrograma::class, 'evaluacion_programa_id');
    }

    public function informe(): HasOne
    {
        return $this->hasOne(InformeEvaluacion::class, 'evaluacion_externa_id');
    }
}
