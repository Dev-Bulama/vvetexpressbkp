<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\ProductProxy;

/**
 * Maps a Bagisto product to the ERPNext Item it was synced from, so repeat
 * syncs can update the existing product instead of creating a duplicate.
 */
class ErpNextProduct extends Model
{
    protected $table = 'marketplace_erpnext_products';

    protected $fillable = [
        'product_id',
        'item_code',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }
}
