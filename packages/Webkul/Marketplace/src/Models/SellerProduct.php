<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Marketplace\Contracts\SellerProduct as SellerProductContract;
use Webkul\Marketplace\Database\Factories\SellerProductFactory;
use Webkul\Product\Models\ProductProxy;

class SellerProduct extends Model implements SellerProductContract
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'marketplace_seller_products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'seller_id',
        'product_id',
        'price',
        'quantity',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price'     => 'decimal:4',
        'quantity'  => 'integer',
        'is_active' => 'boolean',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProxy::modelClass(), 'seller_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    public function inStock(): bool
    {
        return $this->is_active && $this->quantity > 0;
    }

    protected static function newFactory(): SellerProductFactory
    {
        return SellerProductFactory::new();
    }
}
