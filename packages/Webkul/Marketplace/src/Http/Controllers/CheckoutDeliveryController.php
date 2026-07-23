<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\Marketplace\Logistics\Services\DeliveryQuoteService;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Services\VendorCartEligibilityService;

/**
 * One order, one vendor: this step only ever quotes delivery from the
 * single vendor chosen at the previous step, never a per-vendor list.
 */
class CheckoutDeliveryController extends Controller
{
    public function __construct(
        protected DeliveryQuoteService $deliveryQuoteService,
        protected VendorCartEligibilityService $eligibilityService
    ) {}

    public function index(): View|RedirectResponse
    {
        $cart = Cart::getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $sellerId = session('marketplace.vendor_selection');

        if (! $sellerId) {
            return redirect()->route('marketplace.checkout.vendor.index');
        }

        $seller = Seller::find($sellerId);

        $dropoffLat = session('marketplace.customer_location.lat');
        $dropoffLng = session('marketplace.customer_location.lng');

        if (! $dropoffLat || ! $dropoffLng) {
            session()->flash('warning', 'Please set your delivery location before choosing a delivery service.');

            return redirect()->route('marketplace.checkout.vendor.index');
        }

        // The vendor could have lost stock between the vendor step and this
        // one - re-check before quoting delivery for it.
        $check = $seller
            ? $this->eligibilityService->isStillEligible($seller, $cart, (float) $dropoffLat, (float) $dropoffLng)
            : null;

        if (! $seller || ! $check->eligible) {
            session()->forget(['marketplace.vendor_selection', 'marketplace.delivery_selection']);
            session()->flash('error', 'The selected vendor can no longer fulfil all the products and quantities in your cart. Please allow the system to find another complete vendor or adjust your cart.');

            return redirect()->route('marketplace.checkout.vendor.index');
        }

        if (! $seller->latitude || ! $seller->longitude) {
            session()->flash('error', 'This vendor has no pickup location configured. Please choose another vendor.');

            return redirect()->route('marketplace.checkout.vendor.index');
        }

        $quotes = $this->deliveryQuoteService->eligibleQuotes(
            $cart->id,
            $seller->id,
            (float) $seller->latitude,
            (float) $seller->longitude,
            (float) $dropoffLat,
            (float) $dropoffLng,
        );

        return view('marketplace::checkout.delivery', [
            'seller' => $seller,
            'quotes' => $quotes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'quote_token' => ['required', 'string'],
        ]);

        $sellerId = session('marketplace.vendor_selection');

        if (! $sellerId) {
            return redirect()->route('marketplace.checkout.vendor.index');
        }

        $quote = $this->deliveryQuoteService->validate($request->input('quote_token'));

        if (! $quote) {
            session()->flash('error', 'This delivery service has expired. Please choose again.');

            return redirect()->route('marketplace.checkout.delivery.index');
        }

        session(['marketplace.delivery_selection' => [
            'seller_id' => $sellerId,
            'quote_token' => $quote->quote_token,
            'fee_minor' => $quote->fee_minor,
            'service_type_name' => $quote->serviceType->name,
            'logistics_provider_id' => $quote->logistics_provider_id,
            'logistics_service_type_id' => $quote->logistics_service_type_id,
        ]]);

        return redirect()->route('shop.checkout.onepage.index');
    }
}
