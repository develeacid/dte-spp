<?php

namespace App\Support\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use Illuminate\Support\Facades\Log;

final class Trazabilidad
{
    public function __construct(
        public readonly string $programaClave,
        public readonly TipoNivelMir $tipoNivel,
        public readonly ?int $componenteOrden,
        public readonly ?int $actividadOrden,
    ) {}

    public static function deNivel(MirNivel $nivel): self
    {
        $programaClave = $nivel->programa?->clave;
        if (! $programaClave) {
            Log::warning('Trazabilidad: programa sin clave', ['nivel_id' => $nivel->id]);
            $programaClave = 'PROG-'.($nivel->programa_presupuestario_id ?? '?');
        }

        $componenteOrden = null;
        $actividadOrden = null;

        if ($nivel->tipo_nivel === TipoNivelMir::COMPONENTE) {
            $componenteOrden = $nivel->orden;
        }

        if ($nivel->tipo_nivel === TipoNivelMir::ACTIVIDAD) {
            $actividadOrden = $nivel->orden;
            $componenteOrden = $nivel->componente?->orden;
        }

        return new self($programaClave, $nivel->tipo_nivel, $componenteOrden, $actividadOrden);
    }

    public static function deIndicador(Indicador $indicador): self
    {
        return self::deNivel($indicador->mirNivel);
    }

    public function clave(): string
    {
        return match ($this->tipoNivel) {
            TipoNivelMir::FIN => "{$this->programaClave}-f",
            TipoNivelMir::PROPOSITO => "{$this->programaClave}-p",
            TipoNivelMir::COMPONENTE => "{$this->programaClave}-c{$this->componenteOrden}",
            TipoNivelMir::ACTIVIDAD => $this->claveActividad(),
        };
    }

    private function claveActividad(): string
    {
        if ($this->componenteOrden === null) {
            Log::warning('Trazabilidad: actividad sin componente padre', [
                'programa' => $this->programaClave,
                'actividad_orden' => $this->actividadOrden,
            ]);

            return "{$this->programaClave}-c?-a{$this->actividadOrden}";
        }

        return "{$this->programaClave}-c{$this->componenteOrden}-a{$this->actividadOrden}";
    }

    public function programa(): string
    {
        return $this->programaClave;
    }

    public function tipoNivelLabel(): string
    {
        return match ($this->tipoNivel) {
            TipoNivelMir::FIN => 'Fin',
            TipoNivelMir::PROPOSITO => 'Propósito',
            TipoNivelMir::COMPONENTE => 'Componente',
            TipoNivelMir::ACTIVIDAD => 'Actividad',
        };
    }

    public function nivel(): string
    {
        return match ($this->tipoNivel) {
            TipoNivelMir::FIN => 'Fin',
            TipoNivelMir::PROPOSITO => 'Propósito',
            TipoNivelMir::COMPONENTE => "Componente {$this->componenteOrden}",
            TipoNivelMir::ACTIVIDAD => "Actividad {$this->actividadOrden} del Componente {$this->componenteOrden}",
        };
    }

    public function nivelCorto(): string
    {
        return match ($this->tipoNivel) {
            TipoNivelMir::FIN => 'F',
            TipoNivelMir::PROPOSITO => 'P',
            TipoNivelMir::COMPONENTE => "C{$this->componenteOrden}",
            TipoNivelMir::ACTIVIDAD => "A{$this->actividadOrden} · C{$this->componenteOrden}",
        };
    }

    public function toArray(): array
    {
        return [
            'clave' => $this->clave(),
            'programa' => $this->programaClave,
            'tipo_nivel' => $this->tipoNivel->name,
            'componente_orden' => $this->componenteOrden,
            'actividad_orden' => $this->actividadOrden,
        ];
    }
}
