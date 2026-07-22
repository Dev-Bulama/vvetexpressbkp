<?php

namespace Webkul\Marketplace\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkul\Marketplace\Models\Seller;

class SellerGuard
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->guard('seller')->check()) {
            return redirect()->route('marketplace.seller.session.index');
        }

        $seller = auth()->guard('seller')->user();

        if ($seller->status !== Seller::STATUS_APPROVED) {
            auth()->guard('seller')->logout();

            session()->flash('warning', 'Your seller account is no longer active.');

            return redirect()->route('marketplace.seller.session.index');
        }

        return $next($request);
    }
}
