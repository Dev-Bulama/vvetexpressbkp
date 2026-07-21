<?php

namespace Webkul\Marketplace\Repositories;

use Webkul\Core\Eloquent\Repository;

class SellerRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\Marketplace\Contracts\Seller';
    }
}
