<?php

namespace App\DTOs;

class ImportedMirData
{
    /**
     * @param  array  $niveles  Array of nivel arrays, each with:
     *                          tipo_nivel, resumen_narrativo, supuestos, orden, componente_idx?,
     *                          indicadores: [{ nombre, formula_texto?, tipo?, dimension?, frecuencia?,
     *                          sentido?, linea_base?, meta?, rangos_semaforo?, clave_unidad?,
     *                          variables: [{ simbolo, nombre }],
     *                          medios: [{ nombre, fuente?, frecuencia? }]
     *                          }]
     *
     * rangos_semaforo?: array plano rango_{verde|amarillo|rojo|rojo_alto}_{min|max} => float|null.
     * clave_unidad? y medios[].frecuencia? los consume MirDiagnosticoService (reglas B3-B7);
     * MirParserService aún no los emite (diagnóstico latente hasta entonces).
     */
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $clave = null,
        public readonly ?int $ejercicioFiscal = null,
        public readonly array $niveles = [],
    ) {}

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'clave' => $this->clave,
            'ejercicio_fiscal' => $this->ejercicioFiscal,
            'niveles' => $this->niveles,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: $data['nombre'] ?? null,
            clave: $data['clave'] ?? null,
            ejercicioFiscal: $data['ejercicio_fiscal'] ?? null,
            niveles: $data['niveles'] ?? [],
        );
    }
}
