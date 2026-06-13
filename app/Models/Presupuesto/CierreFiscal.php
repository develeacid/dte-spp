<?php

namespace App\Models\Presupuesto;

use App\Enums\EstadoCierreFiscal;
use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ciclo de cierre fiscal de un programa para un ejercicio. El programa es
 * multianual; cada ejercicio tiene su propio ciclo (PREVALIDACION →
 * CONSOLIDACION → FIRMA → CERRADO).
 */
class CierreFiscal extends Model
{
    use HasFactory;

    protected $table = 'cierres_fiscales';

    protected $fillable = [
        'programa_id',
        'ejercicio_fiscal',
        'estado',
        'historial',
    ];

    protected function casts(): array
    {
        return [
            'ejercicio_fiscal' => 'integer',
            'estado' => EstadoCierreFiscal::class,
            'historial' => 'array',
        ];
    }

    public function estaCerrado(): bool
    {
        return $this->estado === EstadoCierreFiscal::CERRADO;
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_id');
    }
}
