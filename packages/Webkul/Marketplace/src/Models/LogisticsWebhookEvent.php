<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsWebhookEvent extends Model
{
    protected $table = 'logistics_webhook_events';

    protected $fillable = [
        'logistics_provider_id',
        'event_type',
        'external_event_id',
        'payload',
        'processed_at',
        'processing_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(LogisticsProvider::class, 'logistics_provider_id');
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }
}
