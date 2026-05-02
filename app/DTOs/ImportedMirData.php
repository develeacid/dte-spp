<?php

namespace App\DTOs;

class ImportedMirData
{
    /**
     * @param  array  $niveles  Array of nivel arrays, each with:
     *                          tipo_nivel, resumen_narrativo, supuestos, orden, componente_idx?,
     *                          indicadores: [{ nombre, formula_texto?, tipo?, dimension?, frecuencia?,
     *                          sentido?, linea_base?, meta?, rangos_semaforo?,
     *                          variables: [{ simbolo, nombre }],
     *                          medios: [{ nombre, fuente? }]
     *                          }]
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
