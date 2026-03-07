<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PedLineaAccion extends Model
{
    protected $table = 'ped_lineas_accion';

    protected $fillable = [
        'ped_estrategia_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ped_estrategia_id' => 'integer',
        ];
    }

    public function estrategia(): BelongsTo
    {
        return $this->belongsTo(PedEstrategia::class, 'ped_estrategia_id');
    }

    public function objetivoEstrategico()
    {
        return $this->estrategia->objetivoEstrategico;
    }

    public function tema()
    {
        return $this->estrategia->objetivoEstrategico->tema;
    }

    public function eje()
    {
        return $this->estrategia->objetivoEstrategico->tema->eje;
    }

    public function plan()
    {
        return $this->estrategia->objetivoEstrategico->tema->eje->plan;
    }

    public function getClaveCompletaAttribute(): string
    {
        return $this->estrategia->clave_completa . '.' . $this->clave;
    }

    // ============================================
    // Relaciones de Alineación
    // ============================================

    /**
     * Objetivos de Programas Derivados alineados a esta Línea de Acción.
     */
    public function programasDerivadosObjetivos(): BelongsToMany
    {
        return $this->belongsToMany(
            ProgramaDerivadoObjetivo::class,
            'alineacion_linea_programa',
            'ped_linea_accion_id',
            'programa_derivado_objetivo_id'
        )->withTimestamps();
    }

    // ============================================
    // Métodos de navegación de cadena completa
    // ============================================

    /**
     * Obtiene todos los ODS a los que contribuye esta Línea de Acción
     * navegando la cadena completa de alineación.
     */
    public function getOdsContribucion(): array
    {
        return $this->load([
            'estrategia.objetivoEstrategico.pndObjetivos.odsMetas.objetivo'
        ])
        ->estrategia
        ->objetivoEstrategico
        ->pndObjetivos
        ->flatMap(fn ($pndObj) => $pndObj->odsMetas)
        ->unique('id')
        ->values()
        ->toArray();
    }
}
