<?php

namespace App\DTOs;

use Illuminate\Database\Eloquent\Model;

readonly class SimilarityResult
{
    /**
     * @param  Model  $model  Modelo encontrado
     * @param  float  $score  Score de similitud (0-1, donde 1 es máxima similitud)
     * @param  float  $distance  Distancia coseno (0-2, donde 0 es idéntico)
     */
    public function __construct(
        public Model $model,
        public float $score,
        public float $distance,
    ) {}

    /**
     * Crea una instancia desde un resultado de query.
     */
    public static function fromQuery(object $result, string $modelClass): self
    {
        /** @var Model $model */
        $model = (new $modelClass)->newInstance();

        $attributes = (array) $result;
        unset($attributes['score'], $attributes['distance'], $attributes['embedding']);

        $model->setRawAttributes($attributes, true);
        $model->exists = true;

        return new self(
            model: $model,
            score: (float) $result->score,
            distance: (float) $result->distance,
        );
    }

    /**
     * Retorna el porcentaje de similitud formateado.
     */
    public function getPercentageAttribute(): string
    {
        return round($this->score * 100, 1).'%';
    }

    /**
     * Indica si el resultado supera un umbral de calidad.
     */
    public function isHighQuality(float $threshold = 0.85): bool
    {
        return $this->score >= $threshold;
    }
}
