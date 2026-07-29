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
    /**
     * No admin decision has been made - SyncErpNextProductsCommand decides
     * visibility automatically based on whether the item has an image and
     * a real price (see isComplete() there).
     */
    public const OVERRIDE_NONE = null;

    /**
     * An admin deliberately hid this item - always wins, regardless of
     * completeness.
     */
    public const OVERRIDE_HIDDEN = 'hidden';

    /**
     * An admin deliberately forced this item public even though it may be
     * incomplete - always wins, regardless of completeness.
     */
    public const OVERRIDE_VISIBLE = 'visible';

    protected $table = 'marketplace_erpnext_products';

    protected $fillable = [
        'product_id',
        'item_code',
        'visibility_override',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    public function isHidden(): bool
    {
        return $this->visibility_override === self::OVERRIDE_HIDDEN;
    }
}
