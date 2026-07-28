<?php

namespace Webkul\Marketplace\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\Marketplace\Http\Controllers\Controller;
use Webkul\Marketplace\Models\ErpNextCategory;

/**
 * Lets an admin see the category tree synced in from the connected ERPNext
 * instance (Item Groups) - the same categories used across the storefront,
 * filters, and product editing - and manually re-run the sync or archive
 * a category ERPNext no longer returns without deleting its API identity.
 */
class ErpNextCategoryController extends Controller
{
    public function index(): View
    {
        $mappings = ErpNextCategory::with('category')
            ->orderBy('external_id')
            ->paginate(50);

        return view('marketplace::admin.erpnext-categories.index', compact('mappings'));
    }

    public function sync(): RedirectResponse
    {
        $exitCode = Artisan::call('erpnext:sync-categories');

        session()->flash(
            $exitCode === 0 ? 'success' : 'error',
            $exitCode === 0
                ? 'Category sync completed. '.trim(Artisan::output())
                : 'Category sync failed: '.trim(Artisan::output())
        );

        return redirect()->route('marketplace.admin.erpnext-categories.index');
    }

    /**
     * One-click switch-over: hides every category that isn't sourced from
     * ERPNext, so the storefront's category nav/homepage/filters (all of
     * which only ever show status=1 categories - see
     * CategoryRepository::getVisibleCategoryTree()) end up showing just
     * the synced API tree. This disables rather than deletes - nothing
     * here is destructive, and any category can be manually re-enabled
     * from the regular Admin > Catalog > Categories screen afterward.
     */
    public function disableNonApiCategories(): RedirectResponse
    {
        $apiCategoryIds = ErpNextCategory::whereNotNull('category_id')->pluck('category_id');

        $rootCategoryIds = Channel::pluck('root_category_id');

        $disabledCount = Category::where('status', 1)
            ->whereNotIn('id', $apiCategoryIds)
            ->whereNotIn('id', $rootCategoryIds)
            ->update(['status' => 0]);

        session()->flash('success', $disabledCount
            ? "Disabled {$disabledCount} non-ERPNext categor".($disabledCount === 1 ? 'y' : 'ies').' - only categories synced from ERPNext are visible on the storefront now.'
            : 'No non-ERPNext categories were enabled - nothing to disable.');

        return redirect()->route('marketplace.admin.erpnext-categories.index');
    }

    public function toggleLocal(int $id): RedirectResponse
    {
        $mapping = ErpNextCategory::findOrFail($id);

        if (! $mapping->category_id) {
            return redirect()
                ->route('marketplace.admin.erpnext-categories.index')
                ->with('error', 'This entry has no local category to toggle (it is the ERPNext tree root).');
        }

        $disable = ! $mapping->is_disabled_locally;

        // A plain status flip isn't a translated attribute, so it goes
        // straight to the model rather than CategoryRepository::update() -
        // that repository method assumes a full locale-keyed admin-form
        // payload and errors on a partial, non-translated-only update.
        Category::where('id', $mapping->category_id)->update(['status' => $disable ? 0 : 1]);

        $mapping->update(['is_disabled_locally' => $disable]);

        session()->flash('success', $disable
            ? 'Category disabled locally - it will stay disabled through future syncs until re-enabled here.'
            : 'Category re-enabled.');

        return redirect()->route('marketplace.admin.erpnext-categories.index');
    }
}
