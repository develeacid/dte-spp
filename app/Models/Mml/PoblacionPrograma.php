<?php

namespace App\Models\Mml;

use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoblacionPrograma extends Model
{
    protected $table = 'poblaciones_programa';

    protected $fillable = [
        'programa_id',
        'unidad_medida',
        'referencia_cantidad',
        'referencia_fuente',
        'potencial_cantidad',
        'potencial_fuente',
        'objetivo_cantidad',
        'objetivo_justificacion',
        'anio_ejercicio',
        'atendida_cantidad',
        'atendida_sync_at',
    ];

    protected $casts = [
        'referencia_cantidad' => 'integer',
        'potencial_cantidad' => 'integer',
        'objetivo_cantidad' => 'integer',
        'anio_ejercicio' => 'integer',
        'atendida_cantidad' => 'integer',
        'atendida_sync_at' => 'datetime',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_id');
    }

    /**
     * % de cobertura del objetivo: atendida real / objetivo planeado * 100.
     * NULL si aún no se ha sincronizado la atendida desde geobase.
     */
    protected function coberturaAtendida(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->atendida_cantidad === null || ! $this->objetivo_cantidad
                ? null
                : round($this->atendida_cantidad / $this->objetivo_cantidad * 100, 2),
        );
    }

    /**
     * Brecha de desempeño: objetivo planeado − atendida real.
     * Positivo = subcobertura; negativo = sobrecobertura. NULL si sin sync.
     */
    protected function brechaAtendida(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->atendida_cantidad === null
                ? null
                : $this->objetivo_cantidad - $this->atendida_cantidad,
        );
    }
}
