<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\Marketplace\Repositories\SellerProductRepository;

class CheckoutVendorController extends Controller
{
    public function __construct(protected SellerProductRepository $sellerProductRepository) {}

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

        return view('marketplace::checkout.vendor', [
            'products' => $products,
            'hasLocation' => (bool) ($latitude && $longitude),
            'sort' => $sort,
            'allSelectable' => $allSelectable,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'vendor' => ['required', 'array'],
            'vendor.*' => ['required', 'integer'],
        ]);

        session(['marketplace.vendor_selection' => $request->input('vendor')]);

        session()->flash('success', 'Vendors selected. Continue to checkout.');

        return redirect()->route('shop.checkout.onepage.index');
    }
}
