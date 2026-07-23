<?php

namespace Webkul\Marketplace\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Webkul\Marketplace\Http\Controllers\Controller;
use Webkul\Marketplace\Models\FailedCartMatch;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Models\VendorReminder;
use Webkul\Marketplace\Services\VendorCatalogueCoverageService;
use Webkul\Marketplace\Services\VendorReminderService;

class VendorCatalogueCoverageController extends Controller
{
    public function __construct(
        protected VendorCatalogueCoverageService $coverageService,
        protected VendorReminderService $reminderService
    ) {}

    public function index(): View
    {
        $vendors = $this->coverageService->allVendorsCoverage();

        $threshold = (float) config('services.marketplace_reminders.coverage_threshold', 50);

        $failedCartMatchCount = FailedCartMatch::where('created_at', '>=', now()->subDays(30))->count();

        $mostCommonMissing = $this->mostCommonMissingProducts($vendors);

        return view('marketplace::admin.catalogue-coverage.index', [
            'vendors' => $vendors,
            'threshold' => $threshold,
            'summary' => [
                'total' => $vendors->count(),
                'complete' => $vendors->where('status', 'complete')->count(),
                'incomplete' => $vendors->whereIn('status', ['incomplete', 'requires_attention'])->count(),
                'below_threshold' => $vendors->where('coverage_percent', '<', $threshold)->count(),
                'no_recent_update' => $vendors->filter(fn ($v) => ! $v->last_inventory_update || Carbon::parse($v->last_inventory_update)->lt(now()->subDays(14)))->count(),
                'failed_cart_matches_30d' => $failedCartMatchCount,
            ],
            'mostCommonMissing' => $mostCommonMissing,
        ]);
    }

    public function show(int $sellerId): View
    {
        $seller = Seller::findOrFail($sellerId);

        $coverage = $this->coverageService->forSeller($seller);
        $missingProducts = $this->coverageService->missingProducts($seller);
        $outOfStockProducts = $this->coverageService->outOfStockProducts($seller);
        $lowStockProducts = $this->coverageService->lowStockProducts($seller);
        $almostEligible = $this->coverageService->almostEligibleSummary($seller);
        $reminders = VendorReminder::where('seller_id', $seller->id)->latest()->get();
        $onCooldown = $this->reminderService->isOnCooldown($seller);

        return view('marketplace::admin.catalogue-coverage.show', compact(
            'seller',
            'coverage',
            'missingProducts',
            'outOfStockProducts',
            'lowStockProducts',
            'almostEligible',
            'reminders',
            'onCooldown'
        ));
    }

    public function remind(Request $request, int $sellerId): RedirectResponse
    {
        $seller = Seller::findOrFail($sellerId);

        $isUrgent = $request->boolean('urgent');

        $reminder = $this->reminderService->sendReminder(
            $seller,
            sentByAdminId: auth()->guard('admin')->id(),
            type: $isUrgent ? 'urgent' : 'manual',
            force: $isUrgent
        );

        session()->flash(
            $reminder ? 'success' : 'error',
            $reminder
                ? 'Reminder sent to '.$seller->shop_name.'.'
                : 'A reminder was already sent to this vendor recently - please wait before sending another, or use "Send Urgent Reminder" to override.'
        );

        return redirect()->route('marketplace.admin.catalogue-coverage.show', $sellerId);
    }

    /**
     * @return Collection<int, array{product_id:int,count:int}>
     */
    protected function mostCommonMissingProducts($vendors)
    {
        $counts = [];

        foreach ($vendors as $vendor) {
            foreach ($this->coverageService->missingProducts($vendor->seller) as $product) {
                $counts[$product->product_id] ??= ['product' => $product, 'vendor_count' => 0];
                $counts[$product->product_id]['vendor_count']++;
            }
        }

        return collect($counts)->sortByDesc('vendor_count')->take(10)->values();
    }
}
