<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\View\View;
use Webkul\Marketplace\Models\Delivery;

class AgentDashboardController extends Controller
{
    public function index(): View
    {
        $agent = auth()->guard('delivery_agent')->user();

        $activeDelivery = Delivery::with(['order', 'seller', 'serviceType'])
            ->where('delivery_agent_id', $agent->id)
            ->whereIn('status', Delivery::ACTIVE_STATUSES)
            ->latest('id')
            ->first();

        return view('marketplace::agent.dashboard', [
            'agent' => $agent,
            'activeDelivery' => $activeDelivery,
        ]);
    }
}
