<?php

namespace App\Models\Mml;

use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MirVersion extends Model
{
    protected $table = 'mir_versiones';

    protected $fillable = [
        'programa_presupuestario_id', 'etiqueta', 'snapshot', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
