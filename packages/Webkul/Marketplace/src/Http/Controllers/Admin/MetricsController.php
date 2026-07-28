<?php

namespace Webkul\Marketplace\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Marketplace\Http\Controllers\Controller;
use Webkul\Marketplace\Services\MetricsService;

class MetricsController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->query('period', '30d');

        if (! in_array($period, ['7d', '30d', '90d', 'all'], true)) {
            $period = '30d';
        }

        $service = MetricsService::forPeriod($period);

        return view('marketplace::admin.metrics.index', [
            'period' => $period,
            'northStars' => $service->northStars(),
            'plots' => $service->plots(),
        ]);
    }
}
