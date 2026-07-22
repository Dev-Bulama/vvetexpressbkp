<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\View\View;

class SellerDashboardController extends Controller
{
    public function index(): View
    {
        $seller = auth()->guard('seller')->user();

        $products = $seller->products()->with('product')->latest()->paginate(20);

        return view('marketplace::seller.dashboard', compact('seller', 'products'));
    }
}
