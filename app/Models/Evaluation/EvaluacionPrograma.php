<?php

namespace App\Models\Evaluation;

use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EvaluacionPrograma extends Model
{
    use LogsActivity;

    protected $table = 'evaluaciones_programa';

    protected $fillable = [
        'programa_presupuestario_id',
        'ejercicio_fiscal',
        'indice_eficacia',
        'desglose_niveles',
        'conteo_semaforos',
        'indicadores_evaluados',
        'indicadores_no_evaluados',
        'configuracion_calculo',
        'analisis_ia',
        'calculado_por',
    ];

    protected function casts(): array
    {
        return [
            'desglose_niveles' => 'array',
            'conteo_semaforos' => 'array',
            'configuracion_calculo' => 'array',
            'indice_eficacia' => 'decimal:4',
            'ejercicio_fiscal' => 'integer',
            'indicadores_evaluados' => 'integer',
            'indicadores_no_evaluados' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'programa_presupuestario_id', 'ejercicio_fiscal',
                'indice_eficacia', 'indicadores_evaluados',
                'indicadores_no_evaluados', 'calculado_por',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "EvaluacionPrograma {$eventName}");
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function calculador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculado_por');
    }
}
