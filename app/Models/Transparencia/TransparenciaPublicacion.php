<?php

namespace App\Models\Transparencia;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransparenciaPublicacion extends Model
{
    use HasFactory;

    protected $table = 'transparencia_publicaciones';

    protected $fillable = [
        'dataset_abierto_id',
        'dataset_clave',
        'action',
        'success',
        'payload_hash',
        'registros_count',
        'publicado_por_user_id',
        'error_message',
        'publicado_at',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'registros_count' => 'integer',
            'publicado_at' => 'datetime',
        ];
    }

    public function datasetAbierto(): BelongsTo
    {
        return $this->belongsTo(DatasetAbierto::class);
    }

    public function publicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publicado_por_user_id');
    }

    public function scopeExitosas(Builder $query): Builder
    {
        return $query->where('success', true);
    }

    public function scopeParaClave(Builder $query, string $clave): Builder
    {
        return $query->where('dataset_clave', $clave);
    }

    protected static function newFactory()
    {
        return \Database\Factories\Transparencia\TransparenciaPublicacionFactory::new();
    }
}
