<?php

namespace Webkul\Marketplace\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Customer\Models\Customer;
use Webkul\Marketplace\Models\Delivery;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Models\SellerProduct;
use Webkul\Sales\Models\Order;

/**
 * Computes the KPIs named in VetXpress's Metrics Field Guide (an internal
 * ownership/definition matrix organised into 11 "Plots" plus 3 "North
 * Star" headline metrics), from whatever real data this system already
 * tracks - orders, sellers, deliveries, carts, catalogue.
 *
 * The source document is a metric *glossary*, not a UI spec: it gives no
 * chart types, thresholds, or page layout, just names/formulas/ownership/
 * cadence. Roughly half its 54 named metrics need data this marketplace
 * doesn't capture at all (ad spend for CAC, a helpdesk for CSAT/ticket
 * metrics, APM for uptime/latency, prescription/license/counterfeit
 * tracking, COGS/accounting data, etc.) - those are returned with
 * tracked=false and a one-line note on what's missing, never a fabricated
 * number, matching this project's standing rule against mock data.
 *
 * All GMV/AOV-style aggregates sum base_grand_total (the platform's base
 * currency), not grand_total, since orders can now be placed in five
 * different currencies (see the currency switcher work) - summing
 * mixed-currency raw totals would silently produce a meaningless number.
 */
class MetricsService
{
    public function __construct(protected Carbon $from, protected Carbon $to) {}

    public static function forPeriod(string $period): self
    {
        $to = now();

        $from = match ($period) {
            '7d' => now()->subDays(7),
            '90d' => now()->subDays(90),
            'all' => Carbon::createFromTimestamp(0),
            default => now()->subDays(30),
        };

        return new self($from, $to);
    }

    /**
     * @return object{gmv: object, repeat_purchase_rate: object, vendor_sla_adherence: object}
     */
    public function northStars(): object
    {
        return (object) [
            'gmv' => $this->metric('GMV', $this->tracked(core()->formatPrice($this->gmv())), 'Daily', 'Overall marketplace health'),
            'repeat_purchase_rate' => $this->metric('Repeat Purchase Rate', $this->tracked($this->formatPercent($this->repeatPurchaseRate())), 'Monthly', 'Stickiness & trust'),
            'vendor_sla_adherence' => $this->metric('Vendor SLA Adherence', $this->slaAdherenceMetric(), 'Weekly', 'Marketplace reliability'),
        ];
    }

    /**
     * @return Collection<int, object> one entry per Plot
     */
    public function plots(): Collection
    {
        return collect([
            $this->plot('01', 'Acquisition', 'Commercial', [
                $this->metric('New consumer sign-ups', $this->tracked((string) $this->newCustomers()), 'Daily'),
                $this->metric('New vendor sign-ups', $this->tracked((string) $this->newVendors()), 'Weekly'),
                $this->untracked('CAC (consumer)', 'Monthly', 'Needs marketing ad-spend data - not tracked in this system.'),
                $this->untracked('CAC (vendor)', 'Monthly', 'Needs vendor onboarding/sales cost data - not tracked.'),
                $this->untracked('Traffic by channel', 'Weekly', 'Needs web/app analytics (UTM/session tracking) - not instrumented.'),
                $this->untracked('Install-to-signup rate', 'Weekly', 'Needs visit/install analytics - not instrumented.'),
            ]),

            $this->plot('02', 'Activation & Engagement', 'Commercial', [
                $this->metric('Time to first order', $this->tracked($this->formatDays($this->avgTimeToFirstOrderDays())), 'Weekly'),
                $this->metric('Activation rate (7-day)', $this->tracked($this->formatPercent($this->activationRate())), 'Weekly'),
                $this->untracked('DAU / MAU', 'Weekly', 'Needs session/activity event tracking - not instrumented.'),
                $this->untracked('Session frequency', 'Weekly', 'Needs session tracking - not instrumented.'),
                $this->untracked('Browse-to-cart rate', 'Weekly', 'Needs page-view/funnel event tracking - not instrumented.'),
                $this->metric('Vendor catalog activation', $this->tracked($this->formatPercent($this->vendorCatalogActivationRate())), 'Weekly'),
            ]),

            $this->plot('03', 'Transactions', 'Commercial', [
                $this->metric('GMV', $this->tracked(core()->formatPrice($this->gmv())), 'Daily'),
                $this->untracked('Net revenue', 'Monthly', 'Needs a platform commission/take-rate model - not tracked.'),
                $this->metric('AOV', $this->tracked(core()->formatPrice($this->aov())), 'Weekly'),
                $this->metric('Cart conversion rate', $this->tracked($this->formatPercent($this->cartConversionRate())), 'Weekly'),
                $this->untracked('Checkout conversion rate', 'Weekly', 'Needs a distinct "checkout started" event - not instrumented.'),
                $this->metric('Cart abandonment rate', $this->tracked($this->formatPercent(100 - $this->cartConversionRate())), 'Weekly'),
                $this->metric('Orders per active buyer', $this->tracked($this->formatNumber($this->ordersPerActiveBuyer(), 2)), 'Monthly'),
                $this->metric('Repeat purchase rate', $this->tracked($this->formatPercent($this->repeatPurchaseRate())), 'Monthly'),
                $this->untracked('Take rate', 'Monthly', 'Needs a platform commission model on product sales - not tracked.'),
            ]),

            $this->plot('04', 'Catalog & Category', 'Operations & Supply', [
                $this->metric('Active SKUs', $this->tracked((string) $this->activeSkuCount()), 'Weekly'),
                $this->metric('Out-of-stock rate', $this->tracked($this->formatPercent($this->outOfStockRate())), 'Weekly'),
                $this->metric('Listing quality score', $this->tracked($this->formatPercent($this->listingQualityScore())), 'Monthly'),
                $this->untracked('Search-to-purchase rate', 'Weekly', 'Needs search-query logging - not instrumented.'),
                $this->untracked('Top searches with no results', 'Weekly', 'Needs search-query logging - not instrumented.'),
            ]),

            $this->plot('05', 'Vendor Health', 'Operations & Supply', [
                $this->metric('Active vendors', $this->tracked((string) $this->activeVendorCount()), 'Monthly'),
                $this->metric('Vendor GMV concentration (top 10%)', $this->tracked($this->formatPercent($this->vendorGmvConcentration())), 'Monthly'),
                $this->metric('Order fulfillment rate', $this->tracked($this->formatPercent($this->orderFulfillmentRate())), 'Weekly'),
                $this->metric('Vendor SLA adherence', $this->slaAdherenceMetric(), 'Weekly'),
                $this->metric('Vendor rating (avg)', $this->tracked($this->formatNumber($this->avgVendorRating(), 1).' / 5'), 'Monthly'),
                $this->metric('Vendor churn rate', $this->tracked($this->formatPercent($this->vendorChurnRate())), 'Monthly'),
                $this->untracked('Vendor payout cycle time', 'Monthly', 'Needs payout/settlement timestamp tracking - not tracked.'),
            ]),

            $this->plot('06', 'Logistics & Fulfillment', 'Operations & Supply', [
                $this->metric('Order-to-delivery time', $this->tracked($this->formatMinutes($this->avgDeliveryMinutes())), 'Weekly'),
                $this->metric('On-time delivery rate', $this->tracked($this->formatPercent($this->onTimeDeliveryRate())), 'Weekly'),
                $this->untracked('Cold-chain compliance (vaccines)', 'Weekly', 'Needs temperature-sensor/IoT data - not tracked.'),
                $this->metric('Delivery cost per order', $this->tracked(core()->formatPrice($this->avgDeliveryFee())), 'Monthly'),
                $this->metric('Return / damage rate', $this->tracked($this->formatPercent($this->returnRate())), 'Weekly'),
            ]),

            $this->plot('07', 'Trust, Safety & Compliance', 'Risk & Trust', [
                $this->untracked('Rx verification compliance', 'Weekly', 'Needs a prescription-verification workflow - not tracked.'),
                $this->untracked('Licensed vendor coverage', 'Monthly', 'Needs vendor license/verification records - not tracked.'),
                $this->untracked('Adverse event reports', 'Weekly', 'Needs a safety-complaint logging workflow - not tracked.'),
                $this->untracked('Counterfeit / quality flags', 'Monthly', 'Needs a quality-flag reporting workflow - not tracked.'),
                $this->untracked('Fraud / chargeback rate', 'Monthly', 'Needs payment-gateway dispute data - not tracked.'),
            ]),

            $this->plot('08', 'Retention & Loyalty', 'Commercial', [
                $this->metric('Buyer retention (30-day)', $this->tracked($this->formatPercent($this->buyerRetentionRate(30))), 'Monthly'),
                $this->metric('Churn rate', $this->tracked($this->formatPercent(100 - $this->buyerRetentionRate(30))), 'Monthly'),
                $this->untracked('Reorder / subscription rate', 'Monthly', 'Needs an auto-reorder/subscription feature - not built.'),
                $this->metric('LTV (avg lifetime spend)', $this->tracked(core()->formatPrice($this->avgLifetimeValue())), 'Quarterly'),
                $this->untracked('LTV : CAC ratio', 'Quarterly', 'Needs CAC, which is not tracked (see Acquisition).'),
                $this->untracked('NPS', 'Quarterly', 'Needs a customer survey mechanism - not built.'),
            ]),

            $this->plot('09', 'Customer Support', 'Enablement', [
                $this->metric('Refund / dispute rate', $this->tracked($this->formatPercent($this->refundRate())), 'Monthly'),
                $this->untracked('CSAT', 'Weekly', 'Needs a support/helpdesk system - not built.'),
                $this->untracked('First response time', 'Weekly', 'Needs a support/helpdesk system - not built.'),
                $this->untracked('Resolution time', 'Weekly', 'Needs a support/helpdesk system - not built.'),
                $this->untracked('Ticket volume by category', 'Weekly', 'Needs a support/helpdesk system - not built.'),
            ]),

            $this->plot('10', 'Platform & Technical Health', 'Enablement', [
                $this->untracked('App / site uptime', 'Daily', 'Needs infrastructure/uptime monitoring - not integrated.'),
                $this->untracked('Payment success rate', 'Daily', 'Needs payment-gateway attempt/failure logs - not tracked.'),
                $this->untracked('Page / app load time', 'Weekly', 'Needs real-user-monitoring (RUM) - not integrated.'),
                $this->untracked('Search latency & relevance', 'Weekly', 'Needs search instrumentation - not tracked.'),
                $this->untracked('Crash rate (app)', 'Weekly', 'Needs a crash-reporting SDK - not integrated.'),
            ]),

            $this->plot('11', 'Financials & Unit Economics', 'Enablement', [
                $this->untracked('Contribution margin per order', 'Monthly', 'Needs COGS/logistics/payment-fee data - not tracked.'),
                $this->untracked('Gross margin', 'Monthly', 'Needs COGS data - not tracked.'),
                $this->untracked('Burn rate', 'Monthly', 'Needs cash-flow/accounting data - not tracked.'),
                $this->untracked('Payback period', 'Quarterly', 'Needs CAC, which is not tracked.'),
            ]),
        ]);
    }

    // ---- Real, computed metrics -------------------------------------

    public function gmv(): float
    {
        return (float) Order::whereBetween('created_at', [$this->from, $this->to])
            ->where('status', '!=', Order::STATUS_CANCELED)
            ->sum('base_grand_total');
    }

    public function aov(): float
    {
        $count = $this->orderCount();

        return $count > 0 ? $this->gmv() / $count : 0.0;
    }

    protected function orderCount(): int
    {
        return Order::whereBetween('created_at', [$this->from, $this->to])
            ->where('status', '!=', Order::STATUS_CANCELED)
            ->count();
    }

    public function newCustomers(): int
    {
        return Customer::whereBetween('created_at', [$this->from, $this->to])->count();
    }

    public function newVendors(): int
    {
        return Seller::whereBetween('created_at', [$this->from, $this->to])->count();
    }

    public function avgTimeToFirstOrderDays(): ?float
    {
        $rows = DB::table('orders')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->whereNotNull('orders.customer_id')
            ->select('customers.created_at as signup', DB::raw('MIN(orders.created_at) as first_order'))
            ->groupBy('orders.customer_id', 'customers.created_at')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $days = $rows->map(fn ($row) => Carbon::parse($row->signup)->diffInHours(Carbon::parse($row->first_order)) / 24);

        return round($days->avg(), 1);
    }

    public function activationRate(): float
    {
        $signups = Customer::whereBetween('created_at', [$this->from, $this->to])->get(['id', 'created_at']);

        if ($signups->isEmpty()) {
            return 0.0;
        }

        $activated = 0;

        foreach ($signups as $customer) {
            $hasEarlyOrder = Order::where('customer_id', $customer->id)
                ->where('created_at', '<=', Carbon::parse($customer->created_at)->addDays(7))
                ->exists();

            if ($hasEarlyOrder) {
                $activated++;
            }
        }

        return round(($activated / $signups->count()) * 100, 2);
    }

    public function vendorCatalogActivationRate(): float
    {
        $approved = Seller::where('status', Seller::STATUS_APPROVED)->count();

        if ($approved === 0) {
            return 0.0;
        }

        $withActiveListing = Seller::where('status', Seller::STATUS_APPROVED)
            ->whereHas('products', fn ($q) => $q->where('is_active', true)->where('quantity', '>', 0))
            ->count();

        return round(($withActiveListing / $approved) * 100, 2);
    }

    public function cartConversionRate(): float
    {
        $carts = DB::table('cart')->whereBetween('created_at', [$this->from, $this->to])->count();

        if ($carts === 0) {
            return 0.0;
        }

        return round(($this->orderCount() / $carts) * 100, 2);
    }

    public function ordersPerActiveBuyer(): float
    {
        $activeBuyers = Order::whereBetween('created_at', [$this->from, $this->to])
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        return $activeBuyers > 0 ? round($this->orderCount() / $activeBuyers, 2) : 0.0;
    }

    public function repeatPurchaseRate(): float
    {
        $buyerOrderCounts = Order::whereBetween('created_at', [$this->from, $this->to])
            ->whereNotNull('customer_id')
            ->select('customer_id', DB::raw('COUNT(*) as order_count'))
            ->groupBy('customer_id')
            ->get();

        if ($buyerOrderCounts->isEmpty()) {
            return 0.0;
        }

        $repeat = $buyerOrderCounts->where('order_count', '>=', 2)->count();

        return round(($repeat / $buyerOrderCounts->count()) * 100, 2);
    }

    public function activeSkuCount(): int
    {
        return DB::table('product_flat')
            ->where('channel', core()->getCurrentChannel()->code)
            ->where('locale', app()->getLocale())
            ->where('status', 1)
            ->where('visible_individually', 1)
            ->count();
    }

    public function outOfStockRate(): float
    {
        $total = SellerProduct::count();

        if ($total === 0) {
            return 0.0;
        }

        return round((SellerProduct::where('quantity', '<=', 0)->count() / $total) * 100, 2);
    }

    /**
     * A product "qualifies" if it has at least one gallery image and a
     * non-empty description - a real, defined completeness rule rather
     * than a fabricated score.
     */
    public function listingQualityScore(): float
    {
        $productIds = $this->activeProductIdsQuery()->pluck('product_id');
        $total = $productIds->count();

        if ($total === 0) {
            return 0.0;
        }

        $withDescription = DB::table('product_flat')
            ->whereIn('product_id', $productIds)
            ->where('channel', core()->getCurrentChannel()->code)
            ->where('locale', app()->getLocale())
            ->whereNotNull('short_description')
            ->where('short_description', '!=', '')
            ->pluck('product_id');

        $withImage = DB::table('product_images')->whereIn('product_id', $productIds)->distinct()->pluck('product_id');

        $complete = $withDescription->intersect($withImage)->count();

        return round(($complete / $total) * 100, 2);
    }

    protected function activeProductIdsQuery()
    {
        return DB::table('product_flat')
            ->where('channel', core()->getCurrentChannel()->code)
            ->where('locale', app()->getLocale())
            ->where('status', 1)
            ->where('visible_individually', 1)
            ->select('product_id');
    }

    public function activeVendorCount(): int
    {
        return Order::whereBetween('created_at', [$this->from, $this->to])
            ->whereNotNull('marketplace_seller_id')
            ->distinct('marketplace_seller_id')
            ->count('marketplace_seller_id');
    }

    public function vendorGmvConcentration(): float
    {
        $gmvBySeller = Order::whereBetween('created_at', [$this->from, $this->to])
            ->where('status', '!=', Order::STATUS_CANCELED)
            ->whereNotNull('marketplace_seller_id')
            ->select('marketplace_seller_id', DB::raw('SUM(base_grand_total) as seller_gmv'))
            ->groupBy('marketplace_seller_id')
            ->orderByDesc('seller_gmv')
            ->get();

        if ($gmvBySeller->isEmpty()) {
            return 0.0;
        }

        $totalGmv = $gmvBySeller->sum('seller_gmv');

        if ($totalGmv <= 0) {
            return 0.0;
        }

        $topCount = max(1, (int) ceil($gmvBySeller->count() * 0.1));
        $topGmv = $gmvBySeller->take($topCount)->sum('seller_gmv');

        return round(($topGmv / $totalGmv) * 100, 2);
    }

    public function orderFulfillmentRate(): float
    {
        $total = $this->orderCount() + Order::whereBetween('created_at', [$this->from, $this->to])->where('status', Order::STATUS_CANCELED)->count();

        if ($total === 0) {
            return 0.0;
        }

        return round(($this->orderCount() / $total) * 100, 2);
    }

    public function avgVendorRating(): float
    {
        return round((float) Seller::where('status', Seller::STATUS_APPROVED)->avg('rating'), 1);
    }

    public function vendorChurnRate(): float
    {
        $priorFrom = $this->from->copy()->subDays($this->to->diffInDays($this->from) ?: 30);

        $priorActive = Order::whereBetween('created_at', [$priorFrom, $this->from])
            ->whereNotNull('marketplace_seller_id')
            ->distinct('marketplace_seller_id')
            ->pluck('marketplace_seller_id');

        if ($priorActive->isEmpty()) {
            return 0.0;
        }

        $stillActive = Order::whereBetween('created_at', [$this->from, $this->to])
            ->whereIn('marketplace_seller_id', $priorActive)
            ->distinct('marketplace_seller_id')
            ->count('marketplace_seller_id');

        $churned = $priorActive->count() - $stillActive;

        return round(($churned / $priorActive->count()) * 100, 2);
    }

    public function avgDeliveryMinutes(): ?float
    {
        $avgSeconds = Delivery::whereNotNull('completed_at')
            ->whereNotNull('requested_at')
            ->whereBetween('completed_at', [$this->from, $this->to])
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, requested_at, completed_at)) as avg_seconds')
            ->value('avg_seconds');

        return $avgSeconds !== null ? round($avgSeconds / 60, 1) : null;
    }

    public function onTimeDeliveryRate(): float
    {
        $deliveries = Delivery::whereNotNull('completed_at')
            ->whereNotNull('requested_at')
            ->whereNotNull('duration_minutes_estimate')
            ->whereBetween('completed_at', [$this->from, $this->to])
            ->get(['requested_at', 'completed_at', 'duration_minutes_estimate']);

        if ($deliveries->isEmpty()) {
            return 0.0;
        }

        $onTime = $deliveries->filter(function ($delivery) {
            $actualMinutes = Carbon::parse($delivery->requested_at)->diffInMinutes(Carbon::parse($delivery->completed_at));

            return $actualMinutes <= $delivery->duration_minutes_estimate;
        })->count();

        return round(($onTime / $deliveries->count()) * 100, 2);
    }

    public function avgDeliveryFee(): float
    {
        $avgMinor = Delivery::whereBetween('created_at', [$this->from, $this->to])->avg('fee_minor');

        return $avgMinor ? round($avgMinor / 100, 2) : 0.0;
    }

    public function returnRate(): float
    {
        $totalOrders = $this->orderCount();

        if ($totalOrders === 0 || ! DB::getSchemaBuilder()->hasTable('rma')) {
            return 0.0;
        }

        $ordersWithReturns = DB::table('rma')
            ->join('orders', 'orders.id', '=', 'rma.order_id')
            ->whereBetween('orders.created_at', [$this->from, $this->to])
            ->distinct('rma.order_id')
            ->count('rma.order_id');

        return round(($ordersWithReturns / $totalOrders) * 100, 2);
    }

    public function buyerRetentionRate(int $windowDays): float
    {
        $currentWindowStart = now()->subDays($windowDays);
        $priorWindowStart = now()->subDays($windowDays * 2);

        $priorBuyers = Order::whereBetween('created_at', [$priorWindowStart, $currentWindowStart])
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->pluck('customer_id');

        if ($priorBuyers->isEmpty()) {
            return 0.0;
        }

        $returning = Order::whereBetween('created_at', [$currentWindowStart, now()])
            ->whereIn('customer_id', $priorBuyers)
            ->distinct('customer_id')
            ->count('customer_id');

        return round(($returning / $priorBuyers->count()) * 100, 2);
    }

    public function avgLifetimeValue(): float
    {
        $totalCustomersWithOrders = Order::whereNotNull('customer_id')->distinct('customer_id')->count('customer_id');

        if ($totalCustomersWithOrders === 0) {
            return 0.0;
        }

        $totalRevenue = Order::where('status', '!=', Order::STATUS_CANCELED)->sum('base_grand_total');

        return round($totalRevenue / $totalCustomersWithOrders, 2);
    }

    public function refundRate(): float
    {
        $totalOrders = $this->orderCount();

        if ($totalOrders === 0) {
            return 0.0;
        }

        $refunded = Order::whereBetween('created_at', [$this->from, $this->to])
            ->where('grand_total_refunded', '>', 0)
            ->count();

        return round(($refunded / $totalOrders) * 100, 2);
    }

    // ---- Formatting / structure helpers -------------------------------

    protected function slaAdherenceMetric(): object
    {
        $rate = $this->onTimeDeliveryRate();
        $hasData = Delivery::whereNotNull('completed_at')->whereNotNull('duration_minutes_estimate')->exists();

        return $hasData
            ? $this->tracked($this->formatPercent($rate))
            : (object) ['value' => null, 'tracked' => false, 'note' => 'No completed deliveries with an SLA estimate yet.'];
    }

    protected function plot(string $number, string $title, string $department, array $metrics): object
    {
        return (object) [
            'number' => $number,
            'title' => $title,
            'department' => $department,
            'metrics' => $metrics,
        ];
    }

    protected function metric(string $label, object $value, string $frequency, ?string $description = null): object
    {
        return (object) [
            'label' => $label,
            'value' => $value->value,
            'tracked' => $value->tracked,
            'note' => $value->note ?? null,
            'frequency' => $frequency,
            'description' => $description,
        ];
    }

    protected function untracked(string $label, string $frequency, string $note): object
    {
        return $this->metric($label, (object) ['value' => null, 'tracked' => false, 'note' => $note], $frequency);
    }

    protected function tracked(string $value): object
    {
        return (object) ['value' => $value, 'tracked' => true, 'note' => null];
    }

    protected function formatPercent(float $value): string
    {
        return number_format($value, 1).'%';
    }

    protected function formatNumber(float $value, int $decimals = 0): string
    {
        return number_format($value, $decimals);
    }

    protected function formatDays(?float $days): string
    {
        return $days === null ? 'N/A' : number_format($days, 1).' days';
    }

    protected function formatMinutes(?float $minutes): string
    {
        return $minutes === null ? 'N/A' : number_format($minutes, 0).' min';
    }
}
