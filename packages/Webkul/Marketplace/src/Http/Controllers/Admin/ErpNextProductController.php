<?php

namespace Webkul\Marketplace\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Marketplace\Concerns\ClearsResponseCache;
use Webkul\Marketplace\Http\Controllers\Controller;
use Webkul\Marketplace\Models\ErpNextProduct;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Lets an admin see every product synced in from the external ERPNext
 * instance and override its storefront visibility - some synced items are
 * confidential and shouldn't be shown even though they exist in the
 * catalog for internal/reporting purposes, and some are auto-hidden by the
 * sync itself for being incomplete (no photo, or no real price) until an
 * admin decides otherwise.
 *
 * "Hidden"/"visible" reuse the product's own status/visible_individually
 * attributes (the same fields that already control whether ANY product is
 * publicly browsable), so toggling one here has exactly the same effect on
 * the storefront as an admin publishing/unpublishing it by hand.
 * visibility_override on the mapping row remembers that this is a
 * deliberate admin decision, distinct from "no decision yet - let the sync
 * decide automatically based on completeness" (see
 * SyncErpNextProductsCommand::syncItem()), so a future sync never silently
 * undoes it either way.
 */
class ErpNextProductController extends Controller
{
    use ClearsResponseCache;

    public function __construct(protected ProductRepository $productRepository) {}

    public function index(Request $request): View
    {
        $query = ErpNextProduct::with(['product', 'product.images', 'product.categories']);

        $uncategorizedOnly = $request->boolean('uncategorized');

        if ($uncategorizedOnly) {
            $query->whereHas('product', function ($productQuery) {
                $productQuery->whereDoesntHave('categories');
            });
        }

        $search = trim((string) $request->query('search'));

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('item_code', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('sku', 'like', "%{$search}%")
                            ->orWhereHas('product_flats', function ($flatQuery) use ($search) {
                                $flatQuery->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $mappings = $query->latest('last_synced_at')->paginate(20)->withQueryString();

        $uncategorizedCount = ErpNextProduct::whereHas('product', function ($productQuery) {
            $productQuery->whereDoesntHave('categories');
        })->count();

        return view('marketplace::admin.erpnext-products.index', compact('mappings', 'uncategorizedCount', 'uncategorizedOnly', 'search'));
    }

    public function toggleVisibility(int $id): RedirectResponse
    {
        $mapping = ErpNextProduct::findOrFail($id);

        $this->applyVisibility($mapping, ! $mapping->isHidden());

        session()->flash('success', $mapping->isHidden()
            ? 'Product hidden from the public storefront.'
            : 'Product is now visible on the public storefront - this overrides the automatic "complete listings only" rule for this item.');

        return redirect()->route('marketplace.admin.erpnext-products.index', $this->currentFilters());
    }

    /**
     * Lets an admin hide or show many synced products in one action -
     * clicking through dozens of confidential/internal items one at a time
     * doesn't scale once there are hundreds synced in, the same reasoning
     * behind the categories page's "Keep Only Checked" bulk action.
     *
     * @param  Request  $request  expects ids[] = array of ErpNextProduct IDs, action = hide|show
     */
    public function bulkUpdateVisibility(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => ['required', 'in:hide,show'],
        ]);

        $ids = collect($request->input('ids', []))->map(fn ($id) => (int) $id)->all();

        $mappings = ErpNextProduct::whereIn('id', $ids)->get();

        foreach ($mappings as $mapping) {
            $this->applyVisibility($mapping, $request->input('action') === 'hide');
        }

        session()->flash('success', count($mappings).' product'.(count($mappings) === 1 ? '' : 's').' '
            .($request->input('action') === 'hide' ? 'hidden from' : 'made visible on').' the public storefront.');

        return redirect()->route('marketplace.admin.erpnext-products.index', $this->currentFilters());
    }

    protected function applyVisibility(ErpNextProduct $mapping, bool $hide): void
    {
        $this->productRepository->update([
            'status' => $hide ? 0 : 1,
            'visible_individually' => $hide ? 0 : 1,
        ], $mapping->product_id);

        $mapping->update([
            'visibility_override' => $hide ? ErpNextProduct::OVERRIDE_HIDDEN : ErpNextProduct::OVERRIDE_VISIBLE,
        ]);

        $this->clearResponseCache();
    }

    /**
     * Carries the current search/uncategorized-filter query params through
     * a redirect, so a bulk action or single toggle doesn't dump the admin
     * back onto an unfiltered page 1 of possibly hundreds of products.
     */
    protected function currentFilters(): array
    {
        return array_filter(request()->only(['search', 'uncategorized', 'page']));
    }
}
