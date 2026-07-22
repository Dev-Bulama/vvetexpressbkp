<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSaleItem extends Model
{
    protected $table = 'marketplace_pos_sale_items';

    protected $fillable = [
        'pos_sale_id',
        'seller_product_id',
        'product_name',
        'price',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'price'      => 'decimal:4',
        'quantity'   => 'integer',
        'line_total' => 'decimal:4',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class);
    }

    public function sellerProduct(): BelongsTo
    {
        return $this->belongsTo(SellerProduct::class);
    }
}
