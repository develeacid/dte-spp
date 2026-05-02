<?php

namespace App\Models\GeoBase;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class WebhookDelivery extends Model
{
    use Prunable;

    protected $table = 'geobase_webhook_deliveries';

    public $timestamps = false;

    protected $fillable = [
        'delivery_id',
        'event_type',
        'signature_valid',
        'payload',
        'status_code',
        'error_message',
        'processed_at',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_valid' => 'boolean',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function prunable(): Builder
    {
        $days = config('services.geobase.delivery_retention_days', 90);

        return static::query()->where('created_at', '<', now()->subDays($days));
    }
}
