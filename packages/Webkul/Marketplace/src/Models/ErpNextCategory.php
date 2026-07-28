<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Category\Models\CategoryProxy;

/**
 * Maps a Bagisto Category to the ERPNext Item Group it was synced from,
 * mirroring ErpNextProduct's mapping pattern (matched on ERPNext's own
 * stable `name` field, not the display label, so a renamed Item Group
 * updates the existing category instead of creating a duplicate).
 */
class ErpNextCategory extends Model
{
    public const STATUS_SYNCED = 'synced';

    public const STATUS_MISSING = 'missing';

    public const STATUS_FAILED = 'failed';

    protected $table = 'marketplace_erpnext_categories';

    protected $fillable = [
        'category_id',
        'external_id',
        'external_parent_id',
        'source',
        'sync_status',
        'external_payload_hash',
        'is_disabled_locally',
        'last_synced_at',
    ];

    protected $casts = [
        'is_disabled_locally' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryProxy::modelClass(), 'category_id');
    }
}
