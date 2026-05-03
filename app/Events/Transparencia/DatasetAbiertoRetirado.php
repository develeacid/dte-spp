<?php

namespace App\Events\Transparencia;

use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DatasetAbiertoRetirado
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DatasetAbierto $dataset,
        public readonly ?User $retirador = null,
    ) {}
}
