<?php

namespace Webkul\Marketplace\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Models\SellerProduct;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [
        Seller::class,
        SellerProduct::class,
    ];
}
