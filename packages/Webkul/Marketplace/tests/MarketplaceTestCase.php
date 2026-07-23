<?php

namespace Webkul\Marketplace\Tests;

use Tests\TestCase;
use Webkul\Admin\Tests\Concerns\AdminTestBench;
use Webkul\Marketplace\Tests\Concerns\MarketplaceTestHelpers;
use Webkul\Shop\Tests\Concerns\ShopTestBench;

class MarketplaceTestCase extends TestCase
{
    use AdminTestBench, MarketplaceTestHelpers, ShopTestBench;
}
