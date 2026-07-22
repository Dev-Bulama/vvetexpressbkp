<?php

namespace Webkul\Marketplace\Logistics\DTOs;

readonly class DeliveryRequest
{
    public function __construct(
        public int $deliveryId,
        public string $pickupAddress,
        public float $pickupLatitude,
        public float $pickupLongitude,
        public string $dropoffAddress,
        public float $dropoffLatitude,
        public float $dropoffLongitude,
    ) {}
}
