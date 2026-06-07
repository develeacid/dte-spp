<?php

namespace App\Models\Evaluation;

use App\Enums\PrioridadRecomendacion;
use Database\Factories\Evaluation\RecomendacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static RecomendacionFactory factory($count = null, $state = [])
 */
class Recomendacion extends Model
{
    use HasFactory;

    protected $table = 'recomendaciones';

    protected $fillable = [
        'hallazgo_id',
        'descripcion',
        'prioridad',
    ];

    protected function casts(): array
    {
        return [
            'prioridad' => PrioridadRecomendacion::class,
        ];
    }

    public function hallazgo(): BelongsTo
    {
        return $this->belongsTo(Hallazgo::class, 'hallazgo_id');
    }

    public function asms(): HasMany
    {
        return $this->hasMany(Asm::class, 'recomendacion_id');
    }
}
