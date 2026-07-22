<?php

namespace Webkul\Marketplace\Listeners;

use Webkul\Marketplace\Logistics\Services\DeliveryService;
use Webkul\Marketplace\Models\DeliveryQuote;
use Webkul\Marketplace\Models\Seller;
use Webkul\Sales\Models\Order;

/**
 * Turns the delivery service(s) chosen at checkout into real deliveries the
 * moment an order actually exists. This only reads from session - it does
 * not trust anything about price from the request itself, since the fee
 * baked into each DeliveryQuote was already computed server-side when the
 * customer picked it.
 */
class CreateDeliveryOnOrderPlaced
{
    public function __construct(protected DeliveryService $deliveryService) {}

    public function handle(Order $order): void
    {
        $deliverySelection = session('marketplace.delivery_selection', []);

        if (empty($deliverySelection)) {
            return;
        }

        foreach ($deliverySelection as $sellerId => $selection) {
            $seller = Seller::find($sellerId);
            $quote = DeliveryQuote::where('quote_token', $selection['quote_token'])->first();

            if (! $seller || ! $quote || $quote->isConsumed()) {
                continue;
            }

            $pickupAddress = trim($seller->address.', '.$seller->city);
            $dropoffAddress = $order->shipping_address
                ? trim($order->shipping_address->address.', '.$order->shipping_address->city)
                : (string) session('marketplace.customer_location.address', '');

            $this->deliveryService->createFromQuote($order, $seller, $quote, $pickupAddress, $dropoffAddress);
        }

        session()->forget(['marketplace.vendor_selection', 'marketplace.delivery_selection']);
    }
}
