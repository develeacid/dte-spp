<?php

namespace App\Models\Mml;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionMeta extends Model
{
    protected $table = 'revisiones_meta';

    protected $fillable = [
        'meta_periodo_id',
        'valor_anterior',
        'valor_nuevo',
        'justificacion',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'valor_anterior' => 'decimal:4',
            'valor_nuevo' => 'decimal:4',
        ];
    }

    public function metaPeriodo(): BelongsTo
    {
        return $this->belongsTo(MetaPeriodo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
