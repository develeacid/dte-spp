<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\Transparencia\DatasetAbierto;

interface PublisherInterface
{
    /**
     * Publica el dataset al portal.
     *
     * @return array{count:int,hash:string} count de filas insertadas y sha256 del payload
     */
    public function publish(DatasetAbierto $dataset): array;

    /**
     * Retira el dataset del portal (vacía la tabla pub_* mapeada).
     */
    public function retire(DatasetAbierto $dataset): void;

    /**
     * Código DS que este publisher maneja (DS-01, DS-02, ...).
     */
    public function code(): string;
}
