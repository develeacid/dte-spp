<?php

namespace App\Observers;

use App\Jobs\Embeddings\GenerateEmbedding;
use Illuminate\Database\Eloquent\Model;

class EmbeddingObserver
{
    /**
     * Nombre del campo de descripción a usar para el embedding.
     */
    protected string $descriptionField = 'descripcion';

    /**
     * Nombre del campo donde guardar el embedding.
     */
    protected string $embeddingField = 'embedding';

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->dispatchEmbeddingJob($model);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        // Solo regenerar si cambió el campo de descripción
        if ($model->isDirty($this->descriptionField)) {
            $this->dispatchEmbeddingJob($model);
        }
    }

    /**
     * Despacha el job de generación de embedding.
     */
    protected function dispatchEmbeddingJob(Model $model): void
    {
        $text = $model->{$this->descriptionField};

        // No generar embedding si no hay texto
        if (empty($text)) {
            return;
        }

        // Despachar job a la cola
        GenerateEmbedding::dispatch(
            get_class($model),
            $model->id,
            $text,
            $this->embeddingField
        );
    }

    /**
     * Configura el campo de descripción personalizado.
     */
    public function setDescriptionField(string $field): self
    {
        $this->descriptionField = $field;

        return $this;
    }

    /**
     * Configura el campo de embedding personalizado.
     */
    public function setEmbeddingField(string $field): self
    {
        $this->embeddingField = $field;

        return $this;
    }
}
