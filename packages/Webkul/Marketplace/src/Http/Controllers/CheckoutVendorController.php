<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartItemRepository;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Models\SellerProduct;
use Webkul\Marketplace\Services\FailedCartMatchRecorder;
use Webkul\Marketplace\Services\VendorCartEligibilityService;

/**
 * VetExpress rule: one buyer -> one complete vendor -> one order. This
 * controller only ever offers vendors capable of fulfilling every product,
 * variation, and quantity in the cart - never a partial match - and the
 * customer picks exactly one vendor for the whole order.
 */
class CheckoutVendorController extends Controller
{
    public function __construct(
        protected VendorCartEligibilityService $eligibilityService,
        protected FailedCartMatchRecorder $failedCartMatchRecorder,
        protected CartItemRepository $cartItemRepository
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $cart = Cart::getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $latitude = $request->query('lat') ?? session('marketplace.customer_location.lat');
        $longitude = $request->query('lng') ?? session('marketplace.customer_location.lng');

        if ($request->filled('lat') && $request->filled('lng')) {
            session(['marketplace.customer_location' => ['lat' => $latitude, 'lng' => $longitude]]);
        }

        $latitude = $latitude ? (float) $latitude : null;
        $longitude = $longitude ? (float) $longitude : null;

        $evaluation = $this->eligibilityService->evaluate($cart, $latitude, $longitude);

        if ($evaluation->eligible->isEmpty()) {
            $this->failedCartMatchRecorder->record($cart, $evaluation, $latitude, $longitude);
        }

        $selectedSellerId = session('marketplace.vendor_selection');

        // A previous selection only survives if it's still in today's
        // eligible list - never let a stale, now-incomplete vendor stay
        // "selected" behind the scenes.
        if ($selectedSellerId && ! $evaluation->eligible->contains(fn ($row) => $row->seller->id === (int) $selectedSellerId)) {
            $selectedSellerId = null;
            session()->forget('marketplace.vendor_selection');
        }

        if (! $selectedSellerId && $evaluation->eligible->isNotEmpty()) {
            $selectedSellerId = $evaluation->eligible->first()->seller->id;
        }

        $eligibleVendors = $this->withCartTotals($evaluation->eligible, $evaluation->lines);

        $customer = auth()->guard('customer')->user();

        $deliveryAddress = $customer?->default_address ?? $customer?->addresses->first();

        return view('marketplace::checkout.vendor', [
            'eligibleVendors' => $eligibleVendors,
            'lines' => $evaluation->lines,
            'selectedSellerId' => $selectedSellerId,
            'hasLocation' => (bool) ($latitude && $longitude),
            'customer' => $customer,
            'deliveryAddress' => $deliveryAddress,
            'cart' => $cart,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'seller_id' => ['required', 'integer'],
        ]);

        $cart = Cart::getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $sellerId = (int) $request->input('seller_id');
        $seller = Seller::find($sellerId);

        $latitude = session('marketplace.customer_location.lat');
        $longitude = session('marketplace.customer_location.lng');

        // Never trust the submitted seller_id at face value - a manipulated
        // request (or one that raced a stock change) must be rejected here,
        // not just filtered out of the list the customer saw.
        $lines = $this->eligibilityService->cartLines($cart);

        $check = $seller
            ? $this->eligibilityService->evaluateSeller(
                $seller,
                $lines,
                $latitude ? (float) $latitude : null,
                $longitude ? (float) $longitude : null
            )
            : null;

        if (! $seller || ! $check->eligible) {
            session()->flash('error', 'The selected vendor can no longer fulfil all the products and quantities in your cart. Please choose another vendor.');

            return redirect()->route('marketplace.checkout.vendor.index');
        }

        session(['marketplace.vendor_selection' => $sellerId]);

        $this->syncCartPricesToSelectedVendor($cart, $sellerId);

        // A previously chosen delivery service belonged to whatever vendor
        // was picked before - clear it so the delivery step is re-quoted
        // for this vendor's actual pickup point.
        session()->forget('marketplace.delivery_selection');

        session()->flash('success', 'Vendor selected. Continue to checkout.');

        return redirect()->route('marketplace.checkout.delivery.index');
    }

    /**
     * Annotates each eligible vendor row with what the whole cart would
     * cost from that vendor specifically, so the customer can compare
     * before picking one.
     */
    protected function withCartTotals($eligibleVendors, $lines)
    {
        $sellerIds = $eligibleVendors->pluck('seller.id');

        $offersBySeller = SellerProduct::whereIn('seller_id', $sellerIds)
            ->whereIn('product_id', $lines->pluck('product_id'))
            ->get()
            ->groupBy('seller_id');

        return $eligibleVendors->map(function ($row) use ($offersBySeller, $lines) {
            $offers = $offersBySeller->get($row->seller->id, collect())->keyBy('product_id');

            $row->cart_total = $lines->sum(fn ($line) => (float) ($offers->get($line->product_id)?->price ?? 0) * $line->quantity);

            $row->line_prices = $lines->mapWithKeys(fn ($line) => [
                $line->product_id => (float) ($offers->get($line->product_id)?->price ?? 0),
            ]);

            return $row;
        });
    }

    /**
     * Bagisto's cart/order totals are otherwise computed from the product's
     * own catalog price, not our per-vendor offer price. Cart items support
     * a `custom_price` override for exactly this - set every item to the
     * one selected vendor's price for that product, since every item in
     * the order now comes from this single vendor.
     */
    protected function syncCartPricesToSelectedVendor($cart, int $sellerId): void
    {
        $offers = SellerProduct::where('seller_id', $sellerId)
            ->where('is_active', true)
            ->get()
            ->keyBy('product_id');

        foreach ($cart->items as $item) {
            $variant = $item->child ?: $item;

            $offer = $offers->get($variant->product_id);

            if (! $offer) {
                continue;
            }

            $this->cartItemRepository->update([
                'custom_price' => $offer->price,
            ], $item->id);
        }

        Cart::collectTotals();
    }
}
