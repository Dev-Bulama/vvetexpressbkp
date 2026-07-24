<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
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
     * Shop name of the one dedicated seller that owns every ERPNext-synced
     * product (see SyncErpNextProductsCommand::systemSeller()). The
     * storefront checks against this to hide vendor attribution for those
     * products - it isn't a real vendor a customer should be shown.
     */
    public const SYSTEM_SELLER_SHOP_NAME = 'External Catalog';

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
        'verification_video_path',
        'verification_video_recorded_at',
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
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'verification_video_recorded_at' => 'datetime',
        'rating' => 'decimal:1',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
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

    public function verificationVideoUrl(): ?string
    {
        return $this->verification_video_path
            ? Storage::disk('public')->url($this->verification_video_path)
            : null;
    }

    protected static function newFactory(): SellerFactory
    {
        return SellerFactory::new();
    }
}
