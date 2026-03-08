<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasEmbedding
{
    /**
     * Get the text that should be embedded for this model.
     * Override in each model to customize.
     */
    public function getEmbeddableText(): string
    {
        $parts = [];

        if (isset($this->nombre) && !empty($this->nombre)) {
            $parts[] = $this->nombre;
        }

        if (isset($this->descripcion) && !empty($this->descripcion)) {
            $parts[] = $this->descripcion;
        }

        if (isset($this->clave) && !empty($this->clave)) {
            array_unshift($parts, $this->clave);
        }

        return implode(' — ', $parts);
    }

    /**
     * Scope to records that need embedding generation.
     */
    public function scopeNeedsEmbedding(Builder $query): Builder
    {
        return $query->whereNull('embedding');
    }

    /**
     * Scope to records that already have an embedding.
     */
    public function scopeHasEmbedding(Builder $query): Builder
    {
        return $query->whereNotNull('embedding');
    }
}
