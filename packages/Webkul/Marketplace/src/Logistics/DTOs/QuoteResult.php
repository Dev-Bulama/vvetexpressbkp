<?php

namespace Webkul\Marketplace\Logistics\DTOs;

readonly class QuoteResult
{
    public function __construct(
        public bool $available,
        public ?float $distanceKm = null,
        public ?int $durationMinutes = null,
        public ?int $feeMinor = null,
        public ?string $currencyCode = null,
        public ?string $unavailableReason = null,
    ) {}

    public static function unavailable(string $reason): self
    {
        return new self(available: false, unavailableReason: $reason);
    }
}
