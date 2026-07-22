<?php

namespace Webkul\Marketplace\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DeliveryAgentGuard
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->guard('delivery_agent')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('marketplace.agent.session.index');
        }

        $agent = auth()->guard('delivery_agent')->user();

        if (! $agent->is_active) {
            auth()->guard('delivery_agent')->logout();

            session()->flash('warning', 'Your delivery agent account is no longer active.');

            return redirect()->route('marketplace.agent.session.index');
        }

        return $next($request);
    }
}
