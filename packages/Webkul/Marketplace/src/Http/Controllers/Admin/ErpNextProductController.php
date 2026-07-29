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
 * instance and hide individual ones from the public storefront - some
 * synced items are confidential (internal-only stock, restricted items)
 * and shouldn't be shown to customers even though they still exist in the
 * catalog for internal/reporting purposes.
 *
 * "Hidden" reuses the product's own status/visible_individually attributes
 * (the same fields that already control whether ANY product is publicly
 * browsable), so hiding one here has exactly the same effect on the
 * storefront as an admin unpublishing it by hand - no separate visibility
 * system to keep in sync. is_hidden_from_public on the mapping row exists
 * purely so the hourly sync command knows not to silently flip it back on.
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

        $mappings = $query->latest('last_synced_at')->paginate(20)->withQueryString();

        $uncategorizedCount = ErpNextProduct::whereHas('product', function ($productQuery) {
            $productQuery->whereDoesntHave('categories');
        })->count();

        return view('marketplace::admin.erpnext-products.index', compact('mappings', 'uncategorizedCount', 'uncategorizedOnly'));
    }

    public function toggleVisibility(int $id): RedirectResponse
    {
        $mapping = ErpNextProduct::findOrFail($id);

        $hide = ! $mapping->is_hidden_from_public;

        $this->productRepository->update([
            'status' => $hide ? 0 : 1,
            'visible_individually' => $hide ? 0 : 1,
        ], $mapping->product_id);

        $mapping->update(['is_hidden_from_public' => $hide]);

        $this->clearResponseCache();

        session()->flash('success', $hide
            ? 'Product hidden from the public storefront.'
            : 'Product is now visible on the public storefront.');

        return redirect()->route('marketplace.admin.erpnext-products.index');
    }
}
