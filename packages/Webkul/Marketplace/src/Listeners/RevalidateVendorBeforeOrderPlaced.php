<?php

namespace Webkul\Marketplace\Listeners;

use Webkul\Checkout\Facades\Cart;
use Webkul\Marketplace\Exceptions\VendorNoLongerEligibleException;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Services\VendorCartEligibilityService;

/**
 * Fires on `checkout.order.save.before` - inside the same DB transaction
 * Bagisto's OrderRepository wraps order creation in, and before any row is
 * written. Stock, the vendor's approval, or the vendor's active status can
 * all have changed since the customer picked this vendor earlier in
 * checkout; this is the last chance to catch that before an order is ever
 * created for a vendor that can no longer fulfil the complete cart.
 */
class RevalidateVendorBeforeOrderPlaced
{
    public function __construct(protected VendorCartEligibilityService $eligibilityService) {}

    public function handle($data): void
    {
        $sellerId = session('marketplace.vendor_selection');

        if (! $sellerId) {
            // No marketplace vendor selection in play for this checkout -
            // not this listener's concern.
            return;
        }

        $cart = Cart::getCart();

        if (! $cart) {
            return;
        }

        $seller = Seller::find($sellerId);

        $latitude = session('marketplace.customer_location.lat');
        $longitude = session('marketplace.customer_location.lng');

        $check = $seller
            ? $this->eligibilityService->isStillEligible(
                $seller,
                $cart,
                $latitude ? (float) $latitude : null,
                $longitude ? (float) $longitude : null
            )
            : null;

        if (! $seller || ! $check->eligible) {
            throw new VendorNoLongerEligibleException;
        }
    }
}
