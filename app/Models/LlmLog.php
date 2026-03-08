<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmLog extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'prompt_template',
        'prompt_version',
        'prompt_text',
        'response_text',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'duration_ms',
        'cost_usd',
        'model',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'duration_ms' => 'integer',
            'cost_usd' => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
