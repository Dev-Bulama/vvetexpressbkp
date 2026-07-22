<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'delivery_status_histories';

    protected $fillable = [
        'delivery_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'note',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
}
