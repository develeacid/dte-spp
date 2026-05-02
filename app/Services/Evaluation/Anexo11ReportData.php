<?php

namespace App\Services\Evaluation;

class Anexo11ReportData
{
    /**
     * @param  array<string,int|string>  $porGenero
     * @param  array<string,int|string>  $porGrupoEdad
     * @param  array<string,int|string>  $porPueblo  clave_etnia => count (or "<5")
     * @param  array<string,int|string>  $porTipoDiscapacidad
     */
    public function __construct(
        public readonly int $programaId,
        public readonly string $programaNombre,
        public readonly int $totalBeneficiarios,
        public readonly array $porGenero,
        public readonly array $porGrupoEdad,
        public readonly array $porPueblo,
        public readonly array $porTipoDiscapacidad,
        public readonly string $refreshedAt,
    ) {}
}
