<?php

namespace App\Livewire\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Services\Mml\IndicadorReglasService;
use App\Services\Mml\MirPrellenadoService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Etapa 5 — Matriz de Indicadores para Resultados')]
class MirEditor extends Component
{
    public ProgramaPresupuestario $programa;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;

        // Prellenar desde EAP si no hay niveles
        (new MirPrellenadoService())->prellenar($programa);
    }

    public function guardarNivel(int $nivelId, string $campo, string $valor): void
    {
        $nivel = MirNivel::findOrFail($nivelId);

        if (in_array($campo, ['resumen_narrativo', 'supuestos'])) {
            $nivel->update([$campo => $valor]);
        }
    }

    public function agregarComponente(): void
    {
        $maxOrden = $this->programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE->value)
            ->max('orden') ?? 0;

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'orden' => $maxOrden + 1,
        ]);
    }

    public function agregarActividad(int $componenteId): void
    {
        $maxOrden = MirNivel::where('componente_id', $componenteId)->max('orden') ?? 0;

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'componente_id' => $componenteId,
            'orden' => $maxOrden + 1,
        ]);
    }

    public function eliminarNivel(int $nivelId): void
    {
        $nivel = MirNivel::findOrFail($nivelId);

        // Only allow deleting Componente/Actividad (not Fin/Propósito)
        if (in_array($nivel->tipo_nivel, [TipoNivelMir::COMPONENTE, TipoNivelMir::ACTIVIDAD])) {
            $nivel->delete();
        }
    }

    public function agregarIndicador(int $nivelId): void
    {
        $nivel = MirNivel::findOrFail($nivelId);
        $reglas = IndicadorReglasService::reglasParaNivel($nivel->tipo_nivel);
        $maxOrden = $nivel->indicadores()->max('orden') ?? 0;

        Indicador::create([
            'mir_nivel_id' => $nivelId,
            'nombre' => '',
            'tipo' => $reglas['tipo_default'],
            'dimension' => $reglas['dimensiones'][0],
            'frecuencia' => $reglas['frecuencias'][0],
            'orden' => $maxOrden + 1,
        ]);
    }

    public function guardarIndicador(int $indicadorId, array $data): void
    {
        $indicador = Indicador::findOrFail($indicadorId);
        $nivel = $indicador->mirNivel;
        $reglas = IndicadorReglasService::reglasParaNivel($nivel->tipo_nivel);

        $validated = validator($data, [
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:' . implode(',', $reglas['tipos']),
            'dimension' => 'required|in:' . implode(',', $reglas['dimensiones']),
            'frecuencia' => 'required|in:' . implode(',', $reglas['frecuencias']),
        ])->validate();

        $indicador->update($validated);
    }

    public function eliminarIndicador(int $indicadorId): void
    {
        Indicador::findOrFail($indicadorId)->delete();
    }

    public function agregarMedioVerificacion(int $indicadorId): void
    {
        $maxOrden = MedioVerificacion::where('indicador_id', $indicadorId)->max('orden') ?? 0;

        MedioVerificacion::create([
            'indicador_id' => $indicadorId,
            'nombre' => '',
            'orden' => $maxOrden + 1,
        ]);
    }

    public function guardarMedioVerificacion(int $medioId, string $nombre, ?string $fuente = null): void
    {
        MedioVerificacion::findOrFail($medioId)->update([
            'nombre' => $nombre,
            'fuente' => $fuente,
        ]);
    }

    public function eliminarMedioVerificacion(int $medioId): void
    {
        MedioVerificacion::findOrFail($medioId)->delete();
    }

    public function render()
    {
        $fin = $this->programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::FIN->value)
            ->first();

        $proposito = $this->programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::PROPOSITO->value)
            ->first();

        $componentes = $this->programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE->value)
            ->with(['actividades.indicadores.mediosVerificacion', 'indicadores.mediosVerificacion'])
            ->orderBy('orden')
            ->get();

        // Load indicadores for fin and proposito
        $fin?->load('indicadores.mediosVerificacion');
        $proposito?->load('indicadores.mediosVerificacion');

        // Build rules map for each nivel type
        $reglasMap = [];
        foreach (TipoNivelMir::cases() as $tipo) {
            $reglasMap[$tipo->value] = IndicadorReglasService::reglasParaNivel($tipo);
        }

        return view('livewire.mml.mir-editor', [
            'fin' => $fin,
            'proposito' => $proposito,
            'componentes' => $componentes,
            'reglasMap' => $reglasMap,
        ]);
    }
}
