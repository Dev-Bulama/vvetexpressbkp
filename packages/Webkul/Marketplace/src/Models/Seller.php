<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Webkul\Marketplace\Contracts\Seller as SellerContract;
use Webkul\Marketplace\Database\Factories\SellerFactory;

class Seller extends Authenticatable implements SellerContract
{
    use HasApiTokens, HasFactory, Notifiable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'marketplace_sellers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'shop_name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'latitude',
        'longitude',
        'status',
        'rating',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude'          => 'decimal:8',
        'longitude'         => 'decimal:8',
        'rating'            => 'decimal:1',
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    /**
     * This seller's product offers.
     */
    public function products(): HasMany
    {
        return $this->hasMany(SellerProductProxy::modelClass(), 'seller_id');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    protected static function newFactory(): SellerFactory
    {
        return SellerFactory::new();
    }
}
