<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\View\View;
use Webkul\Marketplace\Models\PosSale;

class SellerDashboardController extends Controller
{
    public function index(): View
    {
        $seller = auth()->guard('seller')->user();

        $products = $seller->products()->with('product')->latest()->paginate(20);

        $todaysSales = PosSale::where('seller_id', $seller->id)->whereDate('created_at', now())->sum('total');

        $lowStockCount = $seller->products()->where('quantity', '<=', 5)->count();

        return view('marketplace::seller.dashboard', compact('seller', 'products', 'todaysSales', 'lowStockCount'));
    }
}
