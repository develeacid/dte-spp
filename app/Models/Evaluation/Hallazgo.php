<?php

namespace App\Models\Evaluation;

use App\Enums\SeveridadHallazgo;
use Database\Factories\Evaluation\HallazgoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static HallazgoFactory factory($count = null, $state = [])
 */
class Hallazgo extends Model
{
    use HasFactory;

    protected $table = 'hallazgos';

    protected $fillable = [
        'informe_evaluacion_id',
        'descripcion',
        'evidencia_url',
        'severidad',
    ];

    protected function casts(): array
    {
        return [
            'severidad' => SeveridadHallazgo::class,
        ];
    }

    public function informe(): BelongsTo
    {
        return $this->belongsTo(InformeEvaluacion::class, 'informe_evaluacion_id');
    }

    public function recomendaciones(): HasMany
    {
        return $this->hasMany(Recomendacion::class, 'hallazgo_id');
    }
}
