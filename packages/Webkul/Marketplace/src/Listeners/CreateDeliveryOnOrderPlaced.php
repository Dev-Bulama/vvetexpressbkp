<?php

namespace Webkul\Marketplace\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Marketplace\Logistics\Services\DeliveryService;
use Webkul\Marketplace\Models\DeliveryQuote;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Models\SellerProduct;
use Webkul\Sales\Models\Order;

/**
 * The moment a real order exists: records which single vendor fulfils it,
 * atomically decrements that vendor's stock, and turns the delivery quote
 * chosen at checkout into a real delivery.
 *
 * RevalidateVendorBeforeOrderPlaced already re-checked full-cart
 * eligibility just before this order was created, but stock is decremented
 * with a row lock and a guarded WHERE clause regardless - defense in depth
 * against two orders racing the same vendor's last unit.
 */
class CreateDeliveryOnOrderPlaced
{
    public function __construct(protected DeliveryService $deliveryService) {}

    public function handle(Order $order): void
    {
        $sellerId = session('marketplace.vendor_selection');

        if (! $sellerId) {
            return;
        }

        $seller = Seller::find($sellerId);

        if (! $seller) {
            session()->forget(['marketplace.vendor_selection', 'marketplace.delivery_selection']);

            return;
        }

        $order->update(['marketplace_seller_id' => $seller->id]);

        $this->decrementStock($order, $seller);

        $deliverySelection = session('marketplace.delivery_selection');
        $quote = $deliverySelection ? DeliveryQuote::where('quote_token', $deliverySelection['quote_token'] ?? null)->first() : null;

        if ($quote && ! $quote->isConsumed()) {
            $pickupAddress = trim($seller->address.', '.$seller->city);
            $dropoffAddress = $order->shipping_address
                ? trim($order->shipping_address->address.', '.$order->shipping_address->city)
                : (string) session('marketplace.customer_location.address', '');

            $this->deliveryService->createFromQuote($order, $seller, $quote, $pickupAddress, $dropoffAddress);
        }

        session()->forget(['marketplace.vendor_selection', 'marketplace.delivery_selection']);
    }

    /**
     * Reserves stock the simplest safe way: decrement it for real,
     * immediately, guarded by a row lock and a WHERE quantity >= required
     * clause so two orders can never both take the same vendor's last unit.
     * A row that fails the guard (oversold between revalidation and this
     * exact moment - a narrow race, not the common case) is logged rather
     * than thrown, since the order itself already exists at this point.
     */
    protected function decrementStock(Order $order, Seller $seller): void
    {
        DB::transaction(function () use ($order, $seller) {
            foreach ($order->items as $item) {
                $variant = $item->child ?: $item;

                $offer = SellerProduct::where('seller_id', $seller->id)
                    ->where('product_id', $variant->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $offer) {
                    continue;
                }

                $updated = SellerProduct::where('id', $offer->id)
                    ->where('quantity', '>=', $item->qty_ordered)
                    ->decrement('quantity', (int) $item->qty_ordered);

                if (! $updated) {
                    Log::warning('Marketplace order oversold vendor stock at placement', [
                        'order_id' => $order->id,
                        'seller_id' => $seller->id,
                        'product_id' => $variant->product_id,
                        'qty_ordered' => $item->qty_ordered,
                        'available_quantity' => $offer->quantity,
                    ]);
                }
            }
        });
    }
}
