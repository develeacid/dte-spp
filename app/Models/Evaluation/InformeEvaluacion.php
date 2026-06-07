<?php

namespace App\Models\Evaluation;

use Database\Factories\Evaluation\InformeEvaluacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static InformeEvaluacionFactory factory($count = null, $state = [])
 */
class InformeEvaluacion extends Model
{
    use HasFactory;

    protected $table = 'informes_evaluacion';

    protected $fillable = [
        'evaluacion_externa_id',
        'resumen_ejecutivo',
        'metodologia',
        'conclusiones',
        'fichas',
    ];

    public function evaluacionExterna(): BelongsTo
    {
        return $this->belongsTo(EvaluacionExterna::class, 'evaluacion_externa_id');
    }

    public function hallazgos(): HasMany
    {
        return $this->hasMany(Hallazgo::class, 'informe_evaluacion_id');
    }
}
