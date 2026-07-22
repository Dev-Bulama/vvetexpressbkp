<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartItemRepository;
use Webkul\Marketplace\Models\SellerProduct;
use Webkul\Marketplace\Repositories\SellerProductRepository;

class CheckoutVendorController extends Controller
{
    public function __construct(
        protected SellerProductRepository $sellerProductRepository,
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

        $sort = $request->query('sort', 'recommended');

        $selection = session('marketplace.vendor_selection', []);

        $products = $cart->items->unique('product_id')->map(function ($item) use ($latitude, $longitude, $sort, $selection) {
            $offers = $this->sellerProductRepository->findOffersForProduct(
                $item->product_id,
                $latitude ? (float) $latitude : null,
                $longitude ? (float) $longitude : null
            );

            $offers = match ($sort) {
                'price' => $offers->sortBy('price')->values(),
                'distance' => $offers->sortBy('distance_km')->values(),
                default => $offers,
            };

            return [
                'cart_item' => $item,
                'offers' => $offers,
                'selected_seller_id' => $selection[$item->product_id] ?? $offers->first()->seller_id ?? null,
            ];
        });

        $allSelectable = $products->every(fn ($row) => $row['offers']->isNotEmpty());

        $customer = auth()->guard('customer')->user();

        $deliveryAddress = $customer?->default_address ?? $customer?->addresses->first();

        return view('marketplace::checkout.vendor', [
            'products' => $products,
            'hasLocation' => (bool) ($latitude && $longitude),
            'sort' => $sort,
            'allSelectable' => $allSelectable,
            'customer' => $customer,
            'deliveryAddress' => $deliveryAddress,
            'cart' => $cart,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'vendor' => ['required', 'array'],
            'vendor.*' => ['required', 'integer'],
        ]);

        $vendorSelection = $request->input('vendor');

        session(['marketplace.vendor_selection' => $vendorSelection]);

        $this->syncCartPricesToSelectedVendors($vendorSelection);

        session()->flash('success', 'Vendors selected. Continue to checkout.');

        return redirect()->route('shop.checkout.onepage.index');
    }

    /**
     * Bagisto's cart/order totals are otherwise computed from the product's
     * own catalog price, not our per-vendor offer price. Cart items support
     * a `custom_price` override for exactly this kind of case - set it to
     * the selected vendor's price so the real checkout total (and the
     * eventual order) reflects what the customer actually agreed to pay.
     *
     * @param  array<int|string, int|string>  $vendorSelection  product_id => seller_id
     */
    protected function syncCartPricesToSelectedVendors(array $vendorSelection): void
    {
        $cart = Cart::getCart();

        if (! $cart) {
            return;
        }

        foreach ($vendorSelection as $productId => $sellerId) {
            $cartItem = $cart->items->firstWhere('product_id', (int) $productId);

            if (! $cartItem) {
                continue;
            }

            $offer = SellerProduct::where('product_id', (int) $productId)
                ->where('seller_id', (int) $sellerId)
                ->where('is_active', true)
                ->first();

            if (! $offer) {
                continue;
            }

            $this->cartItemRepository->update([
                'custom_price' => $offer->price,
            ], $cartItem->id);
        }

        Cart::collectTotals();
    }
}
