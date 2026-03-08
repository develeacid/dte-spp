<?php

namespace App\Models\Mml;

use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportacionReporte extends Model
{
    protected $table = 'importacion_reportes';

    protected $fillable = [
        'programa_presupuestario_id',
        'team_id',
        'archivo_original',
        'formato',
        'datos_parseados',
        'diagnostico',
        'estado',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'datos_parseados' => 'array',
            'diagnostico' => 'array',
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
