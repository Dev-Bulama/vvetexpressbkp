<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\View\View;
use Webkul\Marketplace\Models\Delivery;

/**
 * Read-only delivery tracking, reachable by whichever of the four parties
 * (customer/seller/agent/admin) is actually involved in this specific
 * delivery - mirrors the same authorization rule used for the Reverb
 * channel (routes/channels.php) so a customer can never see another
 * customer's delivery just by guessing an ID.
 */
class DeliveryTrackingController extends Controller
{
    public function show(int $id): View
    {
        $delivery = Delivery::with(['order', 'seller', 'agent.vehicle', 'serviceType.provider', 'statusHistory'])
            ->findOrFail($id);

        $viewerRole = $this->resolveViewerRole($delivery);

        return view('marketplace::tracking.show', [
            'delivery' => $delivery,
            'viewerRole' => $viewerRole,
            'showPickupCode' => in_array($viewerRole, ['seller', 'agent', 'admin'], true),
            'showDropoffCode' => in_array($viewerRole, ['customer', 'agent', 'admin'], true),
            'mapsApiKey' => config('services.google_maps.api_key'),
            'mapId' => config('services.google_maps.map_id'),
        ]);
    }

    /**
     * Resolves which of the four authorized parties is viewing this
     * delivery, so the view can mask pickup/dropoff verification codes to
     * only the two sides of each handoff (never show a customer the
     * seller's pickup code, or the seller the customer's dropoff code).
     */
    private function resolveViewerRole(Delivery $delivery): string
    {
        $customer = auth()->guard('customer')->user();

        if ($customer && $delivery->customer_id === $customer->id) {
            return 'customer';
        }

        $seller = auth()->guard('seller')->user();

        if ($seller && $delivery->seller_id === $seller->id) {
            return 'seller';
        }

        $agent = auth()->guard('delivery_agent')->user();

        if ($agent && $delivery->delivery_agent_id === $agent->id) {
            return 'agent';
        }

        if (auth()->guard('admin')->check()) {
            return 'admin';
        }

        throw new AuthorizationException('You are not authorized to view this delivery.');
    }
}
