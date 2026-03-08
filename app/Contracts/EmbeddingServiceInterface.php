<?php

namespace App\Contracts;

interface EmbeddingServiceInterface
{
    /**
     * Genera un embedding vectorial para el texto proporcionado.
     *
     * @param string $text Texto a convertir en embedding
     * @return array Array de floats (dimensión según modelo, típicamente 1536)
     * @throws \InvalidArgumentException Si el texto está vacío
     * @throws \RuntimeException Si el API falla
     */
    public function generate(string $text): array;

    /**
     * Obtiene la dimensión del embedding (número de elementos).
     */
    public function getDimension(): int;

    /**
     * Obtiene el modelo de embeddings configurado.
     */
    public function getModel(): string;
}
