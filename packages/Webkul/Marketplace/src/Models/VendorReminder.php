<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorReminder extends Model
{
    protected $table = 'marketplace_vendor_reminders';

    protected $fillable = [
        'seller_id',
        'sent_by_admin_id',
        'type',
        'channel',
        'coverage_percent_at_send',
        'missing_products_count',
        'product_ids',
        'delivery_status',
        'read_at',
        'follow_up_at',
        'admin_notes',
    ];

    protected $casts = [
        'product_ids' => 'array',
        'read_at' => 'datetime',
        'follow_up_at' => 'datetime',
        'coverage_percent_at_send' => 'float',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }
}
