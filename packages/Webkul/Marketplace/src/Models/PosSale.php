<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    protected $table = 'marketplace_pos_sales';

    protected $fillable = [
        'seller_id',
        'reference',
        'total',
        'payment_method',
        'customer_name',
    ];

    protected $casts = [
        'total' => 'decimal:4',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProxy::modelClass(), 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }
}
