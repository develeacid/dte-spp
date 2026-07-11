<?php

namespace App\Models\Mml;

use App\Enums\InvolucradoCategoria;
use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Involucrado extends Model
{
    protected $table = 'involucrados';

    protected $fillable = [
        'programa_presupuestario_id',
        'categoria',
        'nombre',
        'interes_o_rol',
        'riesgo_asociado',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'categoria' => InvolucradoCategoria::class,
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }
}
