<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Marketplace\Repositories\SellerProductRepository;
use Webkul\Product\Repositories\ProductRepository;

class SellerProductController extends Controller
{
    public function __construct(
        protected SellerProductRepository $sellerProductRepository,
        protected ProductRepository $productRepository
    ) {}

    public function index(): View
    {
        $seller = auth()->guard('seller')->user();

        $offers = $seller->products()->with('product')->latest()->paginate(20);

        return view('marketplace::seller.products.index', compact('offers'));
    }

    public function create(Request $request): View
    {
        $results = collect();

        if ($search = $request->query('q')) {
            $results = $this->productRepository->searchFromDatabase([
                'query' => $search,
                'limit' => 10,
                'type' => 'simple',
            ]);
        }

        return view('marketplace::seller.products.create', [
            'results' => $results,
            'search' => $search ?? '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $seller = auth()->guard('seller')->user();

        $existing = $this->sellerProductRepository->findWhere([
            'seller_id' => $seller->id,
            'product_id' => $request->input('product_id'),
        ])->first();

        if ($existing) {
            session()->flash('error', 'You already have an offer for this product. Edit it instead.');

            return redirect()->route('marketplace.seller.products.index');
        }

        $this->sellerProductRepository->create([
            'seller_id' => $seller->id,
            'product_id' => $request->input('product_id'),
            'price' => $request->input('price'),
            'quantity' => $request->input('quantity'),
            'is_active' => true,
        ]);

        session()->flash('success', 'Product offer added successfully.');

        return redirect()->route('marketplace.seller.products.index');
    }

    public function edit(int $id): View
    {
        $offer = $this->authorizedOffer($id);

        return view('marketplace::seller.products.edit', compact('offer'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $offer = $this->authorizedOffer($id);

        $this->sellerProductRepository->update([
            'price' => $request->input('price'),
            'quantity' => $request->input('quantity'),
            'is_active' => $request->boolean('is_active'),
        ], $offer->id);

        session()->flash('success', 'Product offer updated successfully.');

        return redirect()->route('marketplace.seller.products.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $offer = $this->authorizedOffer($id);

        $this->sellerProductRepository->delete($offer->id);

        session()->flash('success', 'Product offer removed.');

        return redirect()->route('marketplace.seller.products.index');
    }

    /**
     * Fetch the offer, guaranteeing it belongs to the logged-in seller.
     */
    private function authorizedOffer(int $id)
    {
        $offer = $this->sellerProductRepository->findOrFail($id);

        if ($offer->seller_id !== auth()->guard('seller')->id()) {
            abort(403);
        }

        return $offer;
    }
}
