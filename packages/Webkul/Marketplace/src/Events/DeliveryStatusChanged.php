<?php

namespace Webkul\Marketplace\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Webkul\Marketplace\Models\Delivery;

class DeliveryStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Delivery $delivery,
        public readonly ?string $fromStatus,
        public readonly string $toStatus,
    ) {}
}
