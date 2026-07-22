<?php

namespace Webkul\Marketplace\Logistics\DTOs;

use Webkul\Marketplace\Models\LogisticsServiceType;

readonly class QuoteRequest
{
    public function __construct(
        public LogisticsServiceType $serviceType,
        public float $pickupLatitude,
        public float $pickupLongitude,
        public float $dropoffLatitude,
        public float $dropoffLongitude,
    ) {}
}
