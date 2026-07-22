<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\Marketplace\Logistics\Services\DeliveryQuoteService;
use Webkul\Marketplace\Models\Seller;

class CheckoutDeliveryController extends Controller
{
    public function __construct(protected DeliveryQuoteService $deliveryQuoteService) {}

    public function index(): View|RedirectResponse
    {
        $cart = Cart::getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $vendorSelection = session('marketplace.vendor_selection', []);

        if (empty($vendorSelection)) {
            return redirect()->route('marketplace.checkout.vendor.index');
        }

        $dropoffLat = session('marketplace.customer_location.lat');
        $dropoffLng = session('marketplace.customer_location.lng');

        if (! $dropoffLat || ! $dropoffLng) {
            session()->flash('warning', 'Please set your delivery location before choosing a delivery service.');

            return redirect()->route('marketplace.checkout.vendor.index');
        }

        $sellerIds = collect($vendorSelection)->unique()->values();

        $sellersWithQuotes = $sellerIds->map(function ($sellerId) use ($cart, $dropoffLat, $dropoffLng) {
            $seller = Seller::find($sellerId);

            if (! $seller || ! $seller->latitude || ! $seller->longitude) {
                return null;
            }

            $productNames = collect($cart->items)
                ->filter(fn ($item) => (int) (session('marketplace.vendor_selection')[$item->product_id] ?? 0) === (int) $sellerId)
                ->pluck('name');

            $quotes = $this->deliveryQuoteService->eligibleQuotes(
                $cart->id,
                $sellerId,
                (float) $seller->latitude,
                (float) $seller->longitude,
                (float) $dropoffLat,
                (float) $dropoffLng,
            );

            return [
                'seller' => $seller,
                'product_names' => $productNames,
                'quotes' => $quotes,
            ];
        })->filter()->values();

        return view('marketplace::checkout.delivery', [
            'sellersWithQuotes' => $sellersWithQuotes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'quote_token' => ['required', 'array'],
            'quote_token.*' => ['required', 'string'],
        ]);

        $selection = [];

        foreach ($request->input('quote_token') as $sellerId => $token) {
            $quote = $this->deliveryQuoteService->validate($token);

            if (! $quote) {
                session()->flash('error', 'One of the selected delivery services has expired. Please choose again.');

                return redirect()->route('marketplace.checkout.delivery.index');
            }

            $selection[$sellerId] = [
                'quote_token' => $quote->quote_token,
                'fee_minor' => $quote->fee_minor,
                'service_type_name' => $quote->serviceType->name,
                'logistics_provider_id' => $quote->logistics_provider_id,
                'logistics_service_type_id' => $quote->logistics_service_type_id,
            ];
        }

        session(['marketplace.delivery_selection' => $selection]);

        return redirect()->route('shop.checkout.onepage.index');
    }
}
