<?php

namespace App\Models\Mml;

use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FichaInformacionBasica extends Model
{
    protected $table = 'fichas_informacion_basica';

    protected $fillable = [
        'programa_presupuestario_id',
        'magnitud',
        'focalizacion',
        'causas_efectos',
        'bienes_servicios',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }
}
